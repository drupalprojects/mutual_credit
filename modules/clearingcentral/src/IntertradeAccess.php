<?php

namespace Drupal\mcapi_cc;

use Drupal\user\Entity\User;
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Wallet;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;

/**
 * Entity access checker for remote transactions.
 */
class IntertradeAccess extends EntityAccessCheck {

  /**
   * Find out which if any of two intertrading directions the user can access.
   *
   * @return AccessResultInterface
   *   The result
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::forbidden();
    $operation = $route->getOptions()['parameters']['operation'];
    $user = User::load($account->id());
    if ($operation == 'credit') {
      // If I control any wallet I can pay out of.
      foreach (Mcapi::walletsOf($user, TRUE) as $wallet) {
        if ($wallet->payways->value != Wallet::PAYWAY_ANYONE_IN) {
          $result = AccessResult::allowed();
        }
      }
    }
    elseif ($operation == 'bill') {
      // If I control any wallet I can pay out of.
      foreach (Mcapi::walletsOf($user, TRUE) as $wallet) {
        if ($wallet->payways->value != Wallet::PAYWAY_ANYONE_OUT) {
          $result = AccessResult::allowed();
        }
      }
    }
    return $result->cachePerUser();
  }

}
