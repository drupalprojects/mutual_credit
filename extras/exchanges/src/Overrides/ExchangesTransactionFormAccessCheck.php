<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Overrides\ExchangesTransactionFormAccessCheck.
 * @todo deprecate this in favour of 'use' op on the Designed form entity
 */

namespace Drupal\mcapi_exchanges\Overrides;

use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessCheck;
use Drupal\Core\Entity\EntityType;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi_exchanges\Entity\Exchanges;//only if enabled!
use Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign;


/**
 * Forms which designate certain exchanges can only be accessed in those exchanges
 */
class ExchangesTransactionFormAccessCheck extends TransactionFormAccessCheck {

  /**
   * The transaction form can only be visited if it is in all exchanges
   * or the user is in the exchange the the form designates.
   *
   * @return AccessResultInterface
   *
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if ($result = parent::access($route, $route_match, $account)->isAllowed()) {
      $editform_id = $route->getOption('parameters')['1stparty_editform'];
      $editform = FirstPartyFormDesign::load($editform_id);
      if ($exchange = Exchange::load($editform->get('exchange'))) {
        //forward the positive result if the current user is in this form's exchange
        if (is_object($exchange) && $exchange->isMember($account)) {
          return $result;
        }
      }
      //forward the positive result if the current user
      elseif (count(Exchanges::in($account, TRUE)) != 0) {
        debug(Exchanges::in($account, TRUE), 'I think this is counting [0 = 0] as 1');
        return $result;
      }
    }
    return AccessResult::forbidden();
  }
}



