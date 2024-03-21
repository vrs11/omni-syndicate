<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\dynamic_ownership\Entity\UserOwnership;

/**
 * Access controller for the User ownership entity.
 *
 * @see \Drupal\dynamic_ownership\Entity\UserOwnership.
 */
class UserOwnershipAccessControlHandler extends EntityAccessControlHandler {

  /**
   * Gets ownership entity status.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param mixed $entity
   *   User ownership entity.
   */
  protected function getOwnershipStatus(AccountInterface $account, $entity) {
    $entity_status = [
      'new' => FALSE,
      'scope' => 'none',
      'roles' => [],
    ];
    $entity_status['roles'] = $account->getRoles();
    if (empty($oid = $entity->get('oid')->value)) {
      $entity_status['new'] = TRUE;
    }
    else {
      if ($entity->get('user_id')->target_id == $account->id()) {
        $entity_status['scope'] = 'own';
        (!empty($role = $entity->get('role_id'))) && ($entity_status['roles'][] = $role->target_id);
      } elseif (!empty($trans = \Drupal::entityTypeManager()->getStorage('user_ownership')->loadByProperties([
        'entity_id__target_id' => $entity->get('entity_id')->target_id,
        'user_id' => $account->id(),
        'state' => 'active',
      ]))) {
        $entity_status['scope'] = 'inherited';
        (!empty($role = reset($trans)->get('role_id'))) && ($entity_status['roles'][] = $role->target_id);
      }
    }

    $entity_status['roles'] = array_unique($entity_status['roles']);

    return $entity_status;
  }

  /**
   * Default field access as determined by this access control handler.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view" or "edit".
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   (optional) The field values for which to check access, or NULL if access
   *   is checked for the field definition, without any specific value
   *   available. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if (
      $operation != 'edit' ||
      !array_key_exists($field_definition->getName(), UserOwnership::DEFAULT_FIELDS)
    ) {
      return parent::checkFieldAccess($operation, $field_definition, $account, $items);
    }

    $entity = $items->getParent();
    $entity_status = $this->getOwnershipStatus($account, $entity);

    return $this->ownershipFieldAccessCheck($operation, $field_definition, $account, $items, $entity_status);
  }

  /**
   * {@inheritDoc}
   */
  protected function ownershipFieldAccessCheck(
    $operation,
    FieldDefinitionInterface $field_definition,
    AccountInterface $account,
    FieldItemListInterface $items,
    $entity_status
  ) {
    if ($entity_status['new']) {
      return parent::checkFieldAccess($operation, $field_definition, $account, $items);
    }

    $field = UserOwnership::DEFAULT_FIELDS[$field_definition->getName()];
    $type_id = $items->getParent()->get('type')->target_id;

    return $this->checkPermissions(
      $items->getParent(),
      "$operation ownership field $field",
      "$operation {$entity_status['scope']} ownership: $type_id field $field",
      $entity_status
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkPermissions($entity, $common_permission, $permission, $entity_status) {
    $access = AccessResult::allowedIf(user_ownership_access_check($common_permission, $entity_status['roles']));
    if (!$access->isAllowed() && user_ownership_access_check($permission, $entity_status['roles'])) {
      $access = $access->orIf(AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity));
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkEntityPermissions($access, $entity, $permissions, $entity_status) {
    foreach ($permissions as $permission => $check) {
      if (!$access->isAllowed() && user_ownership_access_check($permission, $entity_status['roles'])) {
        $access = $access->orIf(AccessResult::allowedIf($check)->cachePerUser()->addCacheableDependency($entity));
      }
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dynamic_ownership\Entity\UserOwnershipInterface $entity */

    $access = AccessResult::neutral();
    $entity_status = $this->getOwnershipStatus($account, $entity);
    $type_id = $entity->get('type')->target_id;

    switch ($operation) {

      case 'view':
        return $this->checkEntityPermissions(
          $access,
          $entity,
          [
            'view any user ownership' => TRUE,
            'view active user ownership' => TRUE,
            "view any ownership: $type_id" => TRUE,
            "view inherited ownership: $type_id" => $entity_status['scope'] == 'inherited',
            "view own ownership: $type_id" => $entity_status['scope'] == 'own',
          ],
          $entity_status
        );

      case 'update':
        return $this->checkEntityPermissions(
          $access,
          $entity,
          [
            'edit any user ownership' => TRUE,
            'edit active user ownership' => TRUE,
            "edit any ownership: $type_id" => TRUE,
            "edit inherited ownership: $type_id" => $entity_status['scope'] == 'inherited',
            "edit own ownership: $type_id" => $entity_status['scope'] == 'own',
          ],
          $entity_status
        );

      case 'delete':
        return $this->checkEntityPermissions(
          $access,
          $entity,
          [
            'delete any user ownership' => TRUE,
            'delete active user ownership' => TRUE,
            "delete any ownership: $type_id" => TRUE,
            "delete inherited ownership: $type_id" => $entity_status['scope'] == 'inherited',
            "delete own ownership: $type_id" => $entity_status['scope'] == 'own',
          ],
          $entity_status
        );

    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $common_permission = "add user ownership";
    $permission = "create ownership: $entity_bundle";
    return AccessResult::allowedIfHasPermission($account, 'add user ownership entities');
  }

}
