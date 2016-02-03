<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\IntertradeAccess.
 * Custom Access control handler for users to intertrade
 *
 */

namespace Drupal\mcapi_cc;

use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;

class IntertradeAccess extends EntityAccessCheck {

  /**
   * Find out whether the user can 'pay' the intertrading wallet of any exchanges of which (s)he is a member
   *
   * @return AccessResultInterface
   * @todo inject EntityTypeManager
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    return AccessResult::allowed();
  }
}

