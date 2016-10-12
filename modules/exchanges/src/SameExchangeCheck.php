<?php

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi_exchanges\Exchanges;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to routes based on login status of current user.
 */
class SameExchangeCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, RouteMatchInterface $routeMatch) {
    if ($membership = mcapi_exchanges_current_membership()) {
      $exchange = $membership->getGroup();
    }
    else return AccessResult::forbidden()->cachePerUser();

    // Get the user we are visiting
    // THERE'S  A PROBLEM WITH THIS ONLY WORKS ON ROUTES WITH UPCAST {user}
    $user = $routeMatch->getParameter('user');

    // Compare exchanges fo the current user with the visited user.
    if ($exchange && in_array($exchange->id(), Exchanges::memberOf($user))) {
      $result = AccessResult::allowed();
    }
    else {
      $result = AccessResult::forbidden();
    }
    return $result->cachePerUser(['user.roles:authenticated']);
  }

}
