<?php

namespace Drupal\group_exclusive;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on the group type
 */
class GroupTypeAccessCheck implements AccessInterface {

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
    $type_names = $route->getRequirement('_group_type');
    // Don't interfere if no permission was specified.
    if ($type_names === NULL) {
      $result = AccessResult::neutral();
    }
    else {
      // Don't interfere if no group was specified.
      $parameters = $route_match->getParameters();
      if (!$parameters->has('group')) {
        $result = AccessResult::neutral();
      }
      else {
        // Don't interfere if the group isn't a real group.
        $group = $parameters->get('group');
        if (!$group instanceof GroupInterface) {
          $result = AccessResult::neutral();
        }
        else {
          if (in_array($group->getGroupType()->id(), explode(';', $type_names))) {
            $result = AccessResult::allowed();
          }
          else {
            $result = AccessResult::forbidden();
          }
        }
      }
    }
    // Or is there a way to cache the result permanently?
    return $result->addCacheableDependency($group);
  }

}
