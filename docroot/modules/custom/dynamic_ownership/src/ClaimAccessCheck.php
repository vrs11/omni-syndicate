<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\dynamic_ownership\Entity\UserOwnershipType;

/**
 * Class ClaimAccessCheck.
 */
class ClaimAccessCheck {

  /**
   * Check users access to claim the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The account proxy.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   AccessResult object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function checkClaimAccess(
    AccountProxy $account,
    EntityInterface $node
  ) {
    $bundle = $node->bundle();
    $type_id = $node->getEntityTypeId();
    $ownership_type = UserOwnershipType::loadDefault("{$type_id}:{$bundle}");

    if (empty($ownership_type)) {
      return AccessResult::forbidden();
    }

    $id = $node->id();
    $uid = $account->id();

    if (
      $account->hasPermission("create ownership: {$ownership_type->id()}") &&
      \Drupal::entityTypeManager()->getStorage('user_ownership')->isClaimAvailable($ownership_type->id(), $id, $type_id, $uid)
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }
}
