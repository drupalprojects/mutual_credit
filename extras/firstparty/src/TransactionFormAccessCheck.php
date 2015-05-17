<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\TransactionFormAccessCheck.
 * Custom Access control handler for Designed transaction forms
 *
 * @see extras/firstparty/src/FirstPartyRoutes
 */

namespace Drupal\mcapi_1stparty;

use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\user\Entity\User;
use Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign;

class TransactionFormAccessCheck extends EntityAccessCheck {

  /**
   * The transaction form is a wrapper around TransactionAccessControlHandler
   * Designed to be overriden by the mcapi_exchanges module
   *
   * @return AccessResultInterface
   *
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {

    $user = User::load($account->id());
    return \Drupal\mcapi\Access\TransactionAccessControlHandler::enoughWallets($user);
  }
}

