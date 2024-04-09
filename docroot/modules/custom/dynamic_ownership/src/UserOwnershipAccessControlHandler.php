<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\dynamic_ownership\Entity\UserOwnership;
use Drupal\dynamic_ownership\Entity\UserOwnershipInterface;

/**
 * Access controller for the User ownership entity.
 *
 * @see \Drupal\dynamic_ownership\Entity\UserOwnership.
 */
class UserOwnershipAccessControlHandler extends EntityAccessControlHandler {

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

    if ($account->hasPermission("{$operation} any user_ownership field")) {
      return AccessResult::allowed();
    }

    $entity = $items->getParent();
    $field = UserOwnership::DEFAULT_FIELDS[$field_definition->getName()];
    $type_id = $items->getParent()->get('type')->target_id;

    if (empty($entity->get('oid')->value)) {
      return AccessResult::allowedIfHasPermissions($account, [
        "{$operation} new user_ownership: field $field",
        "{$operation} new user_ownership: $type_id field $field",
      ]);
    }

    $isOwn = $entity->get('user_id')->target_id == $account->id();
    return AccessResult::allowedIfHasPermissions($account, array_keys(array_filter([
      "{$operation} any user_ownership field" => TRUE,
      "{$operation} user_ownership field $field" => TRUE,
      "{$operation} any user_ownership: $type_id field" => TRUE,
      "{$operation} own user_ownership: $type_id field" => $isOwn,
      "{$operation} any user_ownership: $type_id field $field" => TRUE,
      "{$operation} own user_ownership: $type_id field $field" => $isOwn,
    ])));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $function = ucwords($operation) . ucwords(__FUNCTION__);
    if (method_exists($this, $function)) {
      return $this->$function($entity, $account);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function ViewCheckAccess(UserOwnershipInterface $entity, AccountInterface $account) {
    $isOwn = $entity->get('user_id')->target_id == $account->id();
    $isActive = $entity->getState() == 'active';
    $type = $entity->getEntityTypeId();

    return AccessResult::allowedIfHasPermissions($account, array_keys(array_filter([
      'view any user_ownership' => TRUE,
      'view own user_ownership' => $isOwn,
      "view any user_ownership: {$type}" => TRUE,
      "view active user_ownership: {$type}" => $isActive,
      "view own user_ownership: {$type}" => $isOwn,
    ])));
  }

  /**
   * {@inheritdoc}
   */
  protected function UpdateCheckAccess(UserOwnershipInterface $entity, AccountInterface $account) {
    $isOwn = $entity->get('user_id')->target_id == $account->id();
    $type = $entity->getEntityTypeId();

    return AccessResult::allowedIfHasPermissions($account, array_keys(array_filter([
      'edit user_ownership' => TRUE,
      'edit own user_ownership' => $isOwn,
      "edit any user_ownership: {$type}" => TRUE,
      "edit own user_ownership: {$type}" => $isOwn,
    ])));
  }

  /**
   * {@inheritdoc}
   */
  protected function DeleteCheckAccess(UserOwnershipInterface $entity, AccountInterface $account) {
    $isOwn = $entity->get('user_id')->target_id == $account->id();
    $type = $entity->getEntityTypeId();

    return AccessResult::allowedIfHasPermissions($account, array_keys(array_filter([
      'delete user_ownership' => TRUE,
      'delete own user_ownership' => $isOwn,
      "delete any user_ownership: {$type}" => TRUE,
      "delete own user_ownership: {$type}" => $isOwn,
    ])));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'add user ownership',
      "create user_ownership: $entity_bundle",
    ]);
  }

}
