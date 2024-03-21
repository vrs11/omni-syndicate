<?php

namespace Drupal\dynamic_ownership\TwigExtension;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dynamic_ownership\Entity\UserOwnershipInterface;
use Drupal\node\NodeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class TwigFunctionExtension.
 */
class AccessChecker extends AbstractExtension {

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
   * {@inheritDoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('dynamicAccessCheck', [$this, 'dynamicAccessCheck']),
      new TwigFunction('dynamicRelatedAccessCheck', [$this, 'dynamicRelatedAccessCheck']),
    ];
  }

  /**
   * Checks dynamic access.
   *
   * @param string $permission
   *   The permission to check.
   * @param \Drupal\node\NodeInterface|null $node
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
  public function dynamicAccessCheck(string $permission, ?NodeInterface $node = NULL, $main_roles = [], ?AccountInterface $account = NULL): bool {
    $is_own = in_array('own', str_word_count($permission, 1));
    assert( $is_own && empty($node), '$permission for own content requires specific $node');

    $account = $account ?? $this->currentUser;
    /** @var \Drupal\dynamic_ownership\UserOwnershipStorageInterface $user_ownership_storage */
    $user_ownership_storage = $this->entityTypeManager->getStorage('user_ownership');
    $user_ownerships = $user_ownership_storage->getOwnerships($node ? $node->id() : 0, 'node', $account->id(), $main_roles);

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

    return $this->entityTypeManager
      ->getStorage('user_role')
      ->isPermissionInRoles($permission, $roles);
  }

  /**
   * Checks dynamic access.
   *
   * @param string $permission
   *   The permission to check.
   * @param \Drupal\dynamic_ownership\Entity\UserOwnershipInterface $ownership
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
  public function dynamicRelatedAccessCheck(string $permission, UserOwnershipInterface $ownership, $main_roles = [], ?AccountInterface $account = NULL): bool {
    $node = $ownership->getNode();
    $account = $account ?? $this->currentUser;

    if (in_array('own', str_word_count($permission, 1))) {
      if ($ownership->getUser()->id() == $account->id()) {

        $roles = $account->getRoles();
        if (!empty($role = $ownership->getRole())) {
          $roles[] = $role->id();
        }

        return $this->entityTypeManager
          ->getStorage('user_role')
          ->isPermissionInRoles($permission, $roles);
      }

      if (empty($node)) {
        return FALSE;
      }
    }

    return $this->dynamicAccessCheck($permission, $node, $main_roles, $account);
  }

}
