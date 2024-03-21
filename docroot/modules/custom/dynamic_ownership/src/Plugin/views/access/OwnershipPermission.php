<?php

namespace Drupal\dynamic_ownership\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\views\access\Permission;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "ownership_permission",
 *   title = @Translation("Ownership permission access"),
 *   help = @Translation("Access will be granted if active user has ownership that has required permission.")
 * )
 */
class OwnershipPermission extends Permission implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    $permission = $this->options['perm'];

    $roles = dynamic_ownership_get_all_user_roles();
    return user_ownership_access_check($permission, $roles);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', 'dynamic_ownership.views.access_checker::ownershipAccessCheck');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return ['user.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [];
  }

}
