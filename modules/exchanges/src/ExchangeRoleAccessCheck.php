<?php

namespace Drupal\mcapi_exchanges;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on roles
 */
class ExchangeRoleAccessCheck implements AccessInterface {

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
   *
   * @deprecated
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $roles = $route->getRequirement('_exchange_role');

    // Don't interfere if no role was specified.
    if ($roles === NULL) {
      return AccessResult::neutral();
    }
    $parameters = $route_match->getParameters();
    // @todo use the new function not the heavy context.
    if (!$parameters->has('group')) {
      if ($memship = group_exclusive_membership_get('exchange')) {
        $group = $memship->getGroup();
      }
      else {
        return AccessResult::neutral();
      }
    }
    else {
      $group = $parameters->get('group');
      if (!$group instanceof GroupInterface) {
        return AccessResult::neutral();
      }
    }
    $roles = explode(';', $roles);
    return GroupAccessResult::allowedIfHasGroupRoles($group, $account, $roles);
  }

}
