<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * User ownership access manager.
 */
class UserOwnershipAccessManager {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * AccessChecker constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   Current user account.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $accountProxy
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $accountProxy->getAccount();
  }

  /**
   * Checks dynamic access.
   *
   * @param string|array $permissions
   *   The permissions to check.
   * @param Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to check permissions against.
   * @param array $main_roles
   *   The owners role, if defined
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user to check permissions against.
   *
   * @return bool
   *   The result of a check
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function dynamicAccessCheck(string|array $permissions, ?EntityInterface $entity = NULL, $main_roles = [], ?AccountInterface $account = NULL): bool {
    $is_own = FALSE;
    !is_array($permissions) && $permissions = [$permissions];
    foreach ($permissions as $permission) {
      if (in_array('own', str_word_count($permission, 1))) {
        $is_own = TRUE;
        break;
      }
    }

    assert( $is_own && empty($entity), '$permission for own entity requires specific $entity');

    $account = $account ?? $this->currentUser;
    /** @var \Drupal\dynamic_ownership\UserOwnershipStorageInterface $user_ownership_storage */
    $user_ownership_storage = $this->entityTypeManager->getStorage('user_ownership');
    $user_ownerships = $user_ownership_storage->getOwnerships($entity ? $entity->id() : 0, $entity?->getEntityTypeId() ?? '', $account->id(), $main_roles);

    if ($is_own && empty($user_ownerships)) {
      return FALSE;
    }

    $roles = [];
    if (!$is_own) {
      $roles = $account->getRoles();
    }

    foreach ($user_ownerships as $user_ownership) {
      if (empty($role = $user_ownership->getRole())) {
        continue;
      }

      $roles[] = $role->id();
    }

    $user_role_storage = $this->entityTypeManager->getStorage('user_role');
    foreach ($permissions as $permission) {
      if ($user_role_storage->isPermissionInRoles($permission, $roles)) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
