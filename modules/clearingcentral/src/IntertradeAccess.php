<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\IntertradeAccess.
 * Custom Route Access control handler for users to intertrade
 */

namespace Drupal\mcapi_cc;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;
use Symfony\Component\Routing\Route;

class IntertradeAccess extends EntityAccessCheck {

  /**
   * Access the route if yoiur exchanges intertrading wallet is configured
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::forbidden();

    $settings = mcapi_cc_settings(intertrading_wallet_id());
    $result = $settings['login'] ? AccessResult::allowed() : AccessResult::forbidden();

    return $result->cachePerUser();
  }
}

