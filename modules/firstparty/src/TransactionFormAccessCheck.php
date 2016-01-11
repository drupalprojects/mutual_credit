<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\TransactionFormAccessCheck.
 * Custom Access control handler for Designed transaction forms
 *
 * @see extras/firstparty/src/Entity/FirstPartyRoutes
 */

namespace Drupal\mcapi_1stparty;

use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityAccessCheck;

class TransactionFormAccessCheck extends EntityAccessCheck {

  /**
   * Implement access control specific to transaction forms
   *
   * @return AccessResultInterface
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    //remember this is in addition to TransactionAccessControlHandler::checkCreateAccess()
    //for some reason neutral is interpreted as forbidden when merged with allowed
    //so though I wanted this to be neutral coz its not used yet, it is allowed for now
    return \Drupal\Core\Access\AccessResult::allowed();
  }

}

