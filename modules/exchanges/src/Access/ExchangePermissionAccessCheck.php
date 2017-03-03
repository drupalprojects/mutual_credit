<?php

namespace Drupal\mcapi_exchanges\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\group\Access\GroupAccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on permissions defined via
 * $module.group_permissions.yml files.
 */
class ExchangePermissionAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $permissions = $route->getRequirement('_exchange_permission');
    // Don't interfere if no permission was specified.
    if ($permissions === NULL) {
      return AccessResult::neutral();
    }
    $membership = mcapi_exchange_current_membership();

    if (!$membership) {
      // Don't interfere if no group was specified.
      return AccessResult::neutral();
    }

    $group = $membership->getGroup();
    // Allow to conjunct the permissions with OR ('+') or AND (',').
    $permission_ids = explode(',', $permissions);
    if (count($permission_ids) > 1) {
      return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $permission_ids, 'AND');
    }
    else {
      $permission_ids = explode('+', $permissions);
      return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $permission_ids, 'OR');
    }
  }

}
