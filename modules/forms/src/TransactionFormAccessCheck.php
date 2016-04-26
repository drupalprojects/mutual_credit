<?php

/**
 * @file
 * Contains \Drupal\mcapi_forms\TransactionFormAccessCheck.
 * Custom Access control handler for Designed transaction forms
 *
 * @deprecated
 */

namespace Drupal\mcapi_forms;

use Symfony\Component\Routing\Route;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
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
    $mode = $route_match->getRouteObject()->getOptions()['parameters']['mode'];
    $settings = EntityFormDisplay::load('mcapi_transaction.mcapi_transaction.'.$mode)->getThirdPartySettings('mcapi_forms');
    if (empty($settings['direction'])) {
      return \Drupal\Core\Access\AccessResult::allowedIfHasPermission($account, 'create 3rdparty transactions');
    }
    else {
      return \Drupal\Core\Access\AccessResult::allowed();
    }
  }

}

