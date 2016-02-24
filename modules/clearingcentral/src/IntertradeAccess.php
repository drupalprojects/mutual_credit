<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\IntertradeAccess.
 * Custom Access control handler for users to intertrade
 */

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Wallet;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;

class IntertradeAccess extends EntityAccessCheck {

  /**
   * find out which if any of the two intertrading directions the user can access
   *
   * @return AccessResultInterface
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::forbidden();
    $operation = $route->getOptions()['parameters']['operation'];
    $user = \Drupal\user\Entity\User::load($account->id());
    if ($operation == 'credit') {
      //if I control any wallet I can pay out of.
      foreach (Mcapi::walletsOf($user, TRUE) as $wallet) {
        if ($wallet->payways->value != Wallet::PAYWAY_ANYONE_IN) {
          $result = AccessResult::allowed();
        }
      }
    }
    elseif ($operation == 'bill') {
      //if I control any wallet I can pay out of.
      foreach (Mcapi::walletsOf($user, TRUE) as $wallet) {
        if ($wallet->payways->value != Wallet::PAYWAY_ANYONE_OUT) {
          $result = AccessResult::allowed();
        }
      }
    }
    return $result->cachePerUser();
  }
}

