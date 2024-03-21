<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\dynamic_ownership\Entity\UserOwnershipInterface;
use Drupal\dynamic_ownership\Event\UserOwnershipActivatedEvent;
use Drupal\dynamic_ownership\Event\UserOwnershipCanceledEvent;
use Drupal\dynamic_ownership\Event\UserOwnershipCreatedEvent;
use Drupal\dynamic_ownership\Event\UserOwnershipDeletedEvent;
use Drupal\dynamic_ownership\Event\UserOwnershipUpdatedEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for tool credits.
 *
 * This extends the base storage class, adding required special handling for
 * tool credits entities.
 */
class UserOwnershipStorage extends SqlContentEntityStorage implements UserOwnershipStorageInterface {

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * Performs storage-specific entity deletion.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects to delete.
   */
   protected function doDelete($entities) {
     foreach ($entities as $entity) {
       $this->eventDispatcher->dispatch(
         new UserOwnershipDeletedEvent($entity),
         UserOwnershipDeletedEvent::EVENT_NAME
       );
     }

     Cache::invalidateTags(['user.permissions']);
     parent::doDelete($entities);
   }

  /**
   * Performs post save entity processing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The saved entity.
   * @param bool $update
   *   Specifies whether the entity is being updated or created.
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    $state = $entity->getState();
    $original_state = $entity->original->getState();

    if ($update) {
      $this->eventDispatcher->dispatch(
        new UserOwnershipUpdatedEvent($entity),
        UserOwnershipUpdatedEvent::EVENT_NAME
      );

      if ($state != $original_state) {
        if ($state == 'canceled') {
          $this->eventDispatcher->dispatch(
            new UserOwnershipCanceledEvent($entity),
            UserOwnershipCanceledEvent::EVENT_NAME
          );
        }
        else {
          $this->eventDispatcher->dispatch(
            new UserOwnershipActivatedEvent($entity),
            UserOwnershipActivatedEvent::EVENT_NAME
          );
        }
      }
    } else {
      $this->eventDispatcher->dispatch(
        new UserOwnershipCreatedEvent($entity),
        UserOwnershipCreatedEvent::EVENT_NAME
      );
    }

    Cache::invalidateTags(['user.permissions']);
    parent::doPostSave($entity, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    /** @var \Drupal\dynamic_ownership\Entity\UserOwnershipInterface $entity */

    if (!$this->isSaveAvailable($entity)) {
      throw new AccessDeniedHttpException('You can not create ownerships of this type:' . $entity->bundle());
    }

    return parent::save($entity);
  }

  /**
   * {@inheritDoc}
   */
  public function isSaveAvailable(UserOwnershipInterface $entity): bool {

    if (
      empty($id = $entity->get('entity_id')->target_id) ||
      empty($tid = $entity->get('entity_id')->target_type) ||
      empty($uid = $entity->get('user_id')->target_id)
    ) {
      return FALSE;
    }

    $oid = NULL;
    if (!$entity->isNew()) {
      $oid = $entity->id();
    }

    return $this->isClaimAvailable($entity->bundle(), $id, $tid, $uid, $oid);
  }

  /**
   * {@inheritDoc}
   */
  public function isNewClaimAvailable(string $type_id, int $user_id): bool {

    /** @var \Drupal\dynamic_ownership\Entity\UserOwnershipType $type */
    $type = $this->entityTypeManager->getStorage('user_ownership_type')->load($type_id);
    $conflicts = $type->getConflictBundles();
    $conflicts = array_merge([$type->id()], $conflicts ?? []);

    $query = $this->database->select($this->getBaseTable())
      ->condition('user_id', $user_id)
      ->condition('type', $conflicts, 'IN');

    if (
      !empty($limit = $type->getLimit()) ||
      $limit != 0
    ) {
      $ids = $query->countQuery()
        ->execute()
        ->fetchField();

      if (($ids * 1) > $limit - 1) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function isClaimAvailable(string $type_id, int $entity_id, string $entity_type, ?int $user_id = NULL, ?int $oid = NULL): bool {

    if (
      empty($oid) &&
      !empty($user_id) &&
      !$this->isNewClaimAvailable($type_id, $user_id)
    ) {
      return FALSE;
    }

    /** @var \Drupal\dynamic_ownership\Entity\UserOwnershipType $type */
    $type = $this->entityTypeManager->getStorage('user_ownership_type')->load($type_id);

    $query = $this->database->select($this->getBaseTable())
      ->condition('entity_id__target_id', $entity_id)
      ->condition('entity_id__target_type', $entity_type)
      ->condition('type', $type->id())
      ->condition('state', 'active');

    if (!empty($user_id)) {
      $query->condition('user_id', $user_id);
    }

    if (!empty($oid)) {
      $query->condition('oid', $oid, '<>');
    }

    $ids = $query
      ->countQuery()
      ->execute()
      ->fetchField();

    if (!empty($limit = $type->getLimit()) && (($ids * 1) > ($limit - 1))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function isOwnershipExists(int $entity_id, string $entity_type, int $user_id, ?string $type = NULL, bool $only_active = FALSE): bool {
    $query = $this->database->select($this->getBaseTable())
      ->condition('entity_id__target_id', $entity_id)
      ->condition('entity_id__target_type', $entity_type)
      ->condition('user_id', $user_id);

    if (!empty($type)) {
      $query->condition('type', $type);
    }

    if ($only_active) {
      $query->condition('state', 'active');
    }

    $ids = $query->countQuery()
      ->execute()
      ->fetchField();

    if (($ids * 1) > 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getOwnerships(int $entity_id, string $entity_type, int $user_id, array $roles = [], mixed $type = NULL, bool $only_active = TRUE, ?bool $first = FALSE): mixed {
    $query = $this->database->select($this->getBaseTable(), 't')
      ->condition('t.entity_id__target_id', $entity_id)
      ->condition('t.entity_id__target_type', $entity_type)
      ->condition('t.user_id', $user_id);

    if (!empty($type)) {
      $query->condition('t.type', $type, is_array($type) ? 'IN' : '=');
    }

    if (!empty($roles)) {
      $query->condition('t.role_id', $roles, 'IN');
    }

    if ($only_active) {
      $query->condition('t.state', 'active');
    }
    else {
      $query->orderBy('t.state', 'ASC');
    }

    $query->fields('t', ['oid']);

    $ids = $query
      ->execute()
      ->fetchAll();

    if (empty($ids)) {
      return [];
    }

    $ids = array_column($ids, 'oid');

    if ($first) {
      $id = reset($ids);
      return $this->load($id);
    }

    return $this->loadMultiple($ids);
  }

  /**
   * {@inheritDoc}
   */
  public function getFirstOwnership(int $entity_id, string $entity_type, int $user_id, array $roles = [], mixed $type = NULL, bool $only_active = TRUE): mixed {
    return $this->getOwnerships($entity_id, $entity_type, $user_id, $roles, $type, $only_active, TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public function getEntityOwnerships(int $entity_id, string $entity_type, array $roles = [], mixed $type = NULL, bool $only_active = TRUE, ?bool $first = FALSE): mixed {
    $query = $this->database->select($this->getBaseTable(), 't')
      ->condition('t.entity_id__target_id', $entity_id)
      ->condition('t.entity_id__target_type', $entity_type);

    if (!empty($type)) {
      $query->condition('t.type', $type, is_array($type) ? 'IN' : '=');
    }

    if (!empty($roles)) {
      $query->condition('t.role_id', $roles, 'IN');
    }

    if ($only_active) {
      $query->condition('t.state', 'active');
    }
    else {
      $query->orderBy('t.state', 'ASC');
    }

    $query->fields('t', ['oid']);

    $ids = $query
      ->execute()
      ->fetchAll();

    if (empty($ids)) {
      return [];
    }

    $ids = array_column($ids, 'oid');

    if ($first) {
      $id = reset($ids);
      return $this->load($id);
    }

    return $this->loadMultiple($ids);
  }

  /**
   * {@inheritDoc}
   */
  public function getFirstEntityOwnership(int $entity_id, string $entity_type, array $roles = [], $type = NULL, bool $only_active = TRUE): mixed {
    return $this->getEntityOwnerships($entity_id, $entity_type, $roles, $type, $only_active, TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public function getUserOwnerships(int $user_id, array $roles = [], array|string $type = NULL, bool $only_active = TRUE, ?bool $first = FALSE): mixed {
    $query = $this->database->select($this->getBaseTable(), 't')
      ->condition('t.user_id', $user_id);

    if (!empty($type)) {
      $query->condition('t.type', $type, is_array($type) ? 'IN' : '=');
    }

    if (!empty($roles)) {
      $query->condition('t.role_id', $roles, 'IN');
    }

    if ($only_active) {
      $query->condition('t.state', 'active');
    }
    else {
      $query->orderBy('t.state', 'ASC');
    }

    $query->fields('t', ['oid']);

    $ids = $query
      ->execute()
      ->fetchAll();

    if (empty($ids)) {
      return [];
    }

    $ids = array_column($ids, 'oid');

    if ($first) {
      $id = reset($ids);
      return $this->load($id);
    }

    return $this->loadMultiple($ids);
  }

  /**
   * {@inheritDoc}
   */
  public function getFirstUserOwnership(int $user_id, array $roles = [], mixed $type = NULL, bool $only_active = TRUE): mixed {
    return $this->getUserOwnerships($user_id, $roles, $type, $only_active, TRUE);
  }
}
