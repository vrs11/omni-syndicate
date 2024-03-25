<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

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
    return match($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, ['view stated_entity_reference', 'administer stated_entity_reference types'], 'OR'),
      'update' => AccessResult::allowedIfHasPermissions($account, ['edit stated_entity_reference', 'administer stated_entity_reference types'], 'OR'),
      'delete' => AccessResult::allowedIfHasPermissions($account, ['delete stated_entity_reference', 'administer stated_entity_reference types'], 'OR'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create stated_entity_reference', 'administer stated_entity_reference types'], 'OR');
  }

}
