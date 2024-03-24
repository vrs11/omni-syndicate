<?php

namespace Drupal\dynamic_ownership\TwigExtension;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dynamic_ownership\UserOwnershipAccessManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class TwigFunctionExtension.
 */
class AccessChecker extends AbstractExtension {

  /**
   * User Ownership Access Manager.
   *
   * @var \Drupal\dynamic_ownership\UserOwnershipAccessManager
   */
  protected UserOwnershipAccessManager $userOwnershipAccessManager;

  /**
   * AccessChecker constructor.
   *
   * @param \Drupal\dynamic_ownership\UserOwnershipAccessManager $user_ownership_access_manager
   *   User Ownership Access Manager.
   */
  public function __construct(
    UserOwnershipAccessManager $user_ownership_access_manager
  ) {
    $this->userOwnershipAccessManager = $user_ownership_access_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('dynamicAccessCheck', [$this, 'dynamicAccessCheck']),
    ];
  }

  /**
   * Checks dynamic access.
   *
   * @param string|array $permissions
   *   The permissions to check.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
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
    return $this->userOwnershipAccessManager->dynamicAccessCheck($permissions, $entity, $main_roles, $account);
  }

}
