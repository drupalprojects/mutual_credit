<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\TransactionFormAccessController.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use \Drupal\Core\Access\AccessInterface;


/**
 * Defines an access controller for adding new wallets to entity types
 */
class TransactionFormAccessController implements StaticAccessCheckInterface {


  public function appliesTo() {
    return '_transaction_editform_access';
  }

  /**
   * The transaction form can only be visited if it is in all exchanges
   * or the user is in the exchange the the form designates.
   *
   * @param Route $route
   * @param Request $request
   * @param AccountInterface $account
   * @return AccessInterface constant
   *   a constant either ALLOW, DENY, or KILL
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    //the route name is also the name of the config item!
    return mcapi_1stparty_access(\Drupal::config($request->attributes->get('_route')))
      ? AccessInterface::ALLOW
      : AccessInterface::DENY;
  }

}

