<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\TransactionFormAccessController.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use \Drupal\Core\Access\AccessInterface;


/**
 * Defines an access controller for adding new wallets to entity types
 */
class TransactionFormAccessController implements AccessCheckInterface {


  /**
   * @inheritDoc
   */
  //this is cached...
  public function applies(Route $route) {
    return array('_transaction_editform_access');
  }

  /**
   * The transaction form can only be visited if it is in all exchanges
   * or the user is in the exchange the the form designates.
   *
   * @param Route $route
   * @param Request $request
   * @param AccountInterface $account
   *
   * @return AccessInterface constant
   *   a constant either ALLOW, DENY, or KILL
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    //hack alert!
    //when this is called the second time by the breadcrumb manager, the $request seems not to have been upcast
    //so we'll cach the first result
    //TODO what are the chances of this being called for more than one form?
    static $result;
    if (!isset($result)) {
      if (!($account instanceOf Drupal\user\UserInterface)) {
        $account = user_load($account->id());
      }
      if ($account->hasPermission('configure mcapi')) {
        $result = AccessInterface::ALLOW;
      }
      else {
        $result = mcapi_1stparty_access($request->attributes->get('1stparty_editform'), $account)
          ? AccessInterface::ALLOW
          : AccessInterface::DENY;
      }
    }
    return $result;
  }

}

