<?php

namespace Drupal\stated_entity_reference;



use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\stated_entity_reference\Entity\StatedEntityReference;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class StatedEntityReferenceStorage
 */
class StatedEntityReferenceStorage extends SqlContentEntityStorage implements StatedEntityReferenceStorageInterface {

  /**
   * {@inheritDoc}
   */
  public function isSaveAvailable(StatedEntityReference $entity): bool {
    $values = [
      'type' => $entity->bundle(),
      'source_entity_id__target_id' => $entity->source_entity_id?->target_id,
      'source_entity_id__target_type' => $entity->source_entity_id?->target_type,
      'target_entity_id__target_id' => $entity->target_entity_id?->target_id,
      'target_entity_id__target_type' => $entity->target_entity_id?->target_type,
    ];

    if (count(array_filter($values)) < 5) {
      return FALSE;
    }

    $q = $this->database->select($this->getBaseTable());
    foreach ($values as $key => $value) {
      $q->condition($key, $value);
    }

    $ids = $q->countQuery()
      ->execute()
      ->fetchField();

    if ($ids) {
      return FALSE;
    }

    return TRUE;
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


  public function establishReference(string $type, EntityInterface $source, EntityInterface $target, $extra = []) {
    if (empty($target->id())) {
      return FALSE;
    }

    $base = [
      'type' => $type,
      'target_entity_id' => $target,
      ...$extra,
    ];

    if (empty($source->id())) {
      $ref = $source->_stated_entity_reference_storage ?? [];
      $ref[] = $base;
      $source->_stated_entity_reference_storage = $ref;
      return TRUE;
    }

    $rel = StatedEntityReference::create(array_merge($base, ['source_entity_id' => $source]));
    if (!$this->isSaveAvailable($rel)) {
      return FALSE;
    }

    return parent::save($rel);
  }
}
