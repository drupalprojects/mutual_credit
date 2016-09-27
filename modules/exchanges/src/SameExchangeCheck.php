<?php

namespace Drupal\mcapi_exchanges\Access;

use Drupal\mcapi_exchanges\Exchanges;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
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
    $exchange = mcapi_exchanges_current_membership()->getGroup();

    // Get the user we are visiting
    // THERE'S  A PROBLEM WITH THIS ONLY WORKS ON ROUTES WITH UPCAST {user}
    $user = $routeMatch->getParameters('user');

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
