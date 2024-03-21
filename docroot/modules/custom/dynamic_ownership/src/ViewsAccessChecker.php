<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Variation add to cart form controller.
 */
class ViewsAccessChecker {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ViewsAccessChecker.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function checkUserAccess(
    AccountInterface $account
  ) {
    if (
      $account->hasPermission('view users user ownership')
      || (
        !empty($user = $this->routeMatch->getParameter('user')) &&
        $account->id() == $user
    )) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function ownershipAccessCheck(
    AccountInterface $account,
    RouteMatchInterface $rout_match
  ): AccessResultNeutral|AccessResult|AccessResultAllowed {
    $permission = $rout_match->getRouteObject()->getRequirement('_permission_to_check');
    $ownership_type = $rout_match->getRouteObject()->getRequirement('_required_ownership_type');
    if (empty($permission)) {
      return AccessResult::neutral('Route is missing permission to check');
    }

    $roles = dynamic_ownership_get_all_user_roles($account, $ownership_type ?? NULL);
    return AccessResult::allowedIf(user_ownership_access_check($permission, $roles));
  }

}
