<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\stated_entity_reference\Entity\StatedEntityReferenceInterface;

/**
 * Defines the access control handler for the Stated entity reference entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class StatedEntityReferenceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $func = __FUNCTION__ . ucwords($operation);
    if (method_exists($this, $func)) {
      return $this->$func($entity, $account);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccessView(StatedEntityReferenceInterface $entity, AccountInterface $account): AccessResult {
    $access = AccessResult::allowedIfHasPermissions($account, [
      'view any stated_entity_reference',
      'administer stated_entity_reference types'
    ], 'OR');

    if (!$access->isAllowed() && $entity->getState() == 'active') {
      $access = $access->orIf(AccessResult::allowedIfHasPermission($account, 'view active stated_entity_reference'));
    }

    if (!$access->isAllowed() && $entity->getSourceEntity()->getOwnerId() == $account->id()) {
      $access = $access->orIf(AccessResult::allowedIfHasPermission($account, 'view own stated_entity_reference'));
    }

    if (!$access->isAllowed() && $entity->getTargetEntity()->getOwnerId() == $account->id()) {
      $access = $access->orIf(AccessResult::allowedIfHasPermission($account, 'view referenced stated_entity_reference'));
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccessUpdate(StatedEntityReferenceInterface $entity, AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['edit stated_entity_reference', 'administer stated_entity_reference types'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccessDelete(StatedEntityReferenceInterface $entity, AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['delete stated_entity_reference', 'administer stated_entity_reference types'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create stated_entity_reference', 'administer stated_entity_reference types'], 'OR');
  }

}
