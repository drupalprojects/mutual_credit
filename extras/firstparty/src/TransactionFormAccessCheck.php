<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\TransactionFormAccessCheck.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use \Drupal\Core\Access\AccessInterface;


/**
 * Forms which designate certain exchanges can only be accessed in those exchanges
 */
class TransactionFormAccessCheck implements AccessCheckInterface {

  /**
   * @inheritDoc
   */
  //not too many examples of this in core to work with
  public function applies(Route $route) {
    if (in_array('editform_id', $route->getOptions())) return TRUE;
  }

  /**
   * The transaction form can only be visited if it is in all exchanges
   * or the user is in the exchange the the form designates.
   *
   * @param AccountInterface $account
   *
   * @return AccessInterface constant
   *   a constant either ALLOW, DENY, or KILL
   *
   * @todo sort this out when the interface has settled down - alpha12
   */
  public function access(AccountInterface $account) {
return AccessInterface::ALLOW;
      if (!($account instanceOf Drupal\user\UserInterface)) {
        $account = user_load($account->id());
      }
      if ($account->hasPermission('configure mcapi')) {
        return AccessInterface::ALLOW;
      }
      else {
//how do we get the form_id we are testing for?

        return $editform && mcapi_1stparty_access($editform, $account)
          ? AccessInterface::ALLOW
          : AccessInterface::DENY;
      }
  }

}

