<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\ExchangeJoinAccess.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;


/**
 * Forms which designate certain exchanges can only be accessed in those exchanges
 */
class ExchangeJoinAccess implements AccessCheckInterface {

  /**
   * @inheritDoc
   */
  //not too many examples of this in core to work with
  public function applies(Route $route) {
    return array_key_exists('_exchange_join_access', $route->getRequirements());
  }

  /**
   * The exchanged can only be joined if it has its anon flag set to true
   * This controller is used in conjunction with a check that the user is anon
   *
   * @param AccountInterface $account
   *
   * @return AccessInterface constant
   *   a constant either ALLOW, DENY, or KILL
   */
  public function access(AccountInterface $account) {

    if ( $account->id() == 0) {
      $result = AccessResult::allowed();

      //TODO how should we get the exchange entity in here?
      //Routematch? Also see mcapi_1stparty\TransactionFormAccessCheck::access

      if ($mcapi_exchange->anon->value = TRUE) {
        return $result = AccessResult::allowed();
      }
    }
    else {//for existing users they can join if the exchange allows
      return $result = AccessResult::allowed();
      return $result = AccessResult::forbidden();
    }

  }

}

