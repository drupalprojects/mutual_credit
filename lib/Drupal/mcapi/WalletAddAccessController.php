<?php

/**
 * @file
 * Contains \Drupal\mcapi\WalletAddAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\StaticAccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use \Drupal\Core\Access\AccessInterface;


/**
 * Defines an access controller for adding new wallets to entity types
 */
class WalletAddAccessController implements StaticAccessCheckInterface {

  private $pluginManager;

  public function appliesTo() {
    return '_wallet_add_access';
  }

  /**
   * Wallets can only be added if:
   *  the current user has permission to create wallets in the exchange of that entity
   *  or
   *  the current user is on their own page and have permission to create own wallets
   * and
   *  the max wallets threshhold is not reached for that entity
   *
   *
   * @param Route $route
   * @param Request $request
   * @param AccountInterface $account
   * @return AccessInterface constant
   *   a constant either ALLOW, DENY, or KILL
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $config = \Drupal::config('mcapi.wallets');
    module_load_include('inc', 'mcapi');
    //this fetches the entity we are viewing - the would-be owner of the wallet we would add
    $owner = mcapi_request_get_entity($request);
    $type = $owner->entityType();

    //quick check first for this common scenario
    if ($type == 'user' && $config->get('entity_types.user') == 1 && $config->get('autoadd_name')) {
      return AccessInterface::DENY;
    }
    //for users to add their own wallets
    if ($account->hasPermission('create own wallets') && $type == 'user' && $account->id() == $owner->id) {
      $access = TRUE;
    }
    //for exchange managers to add wallets to any entity of that exchange
    elseif ($account->hasPermission('manage own exchanges')) {
      //is there a better way to
      if(!is_a($account, 'Drupal\user\Entity\User')) {
        //that means we've been passed a userSession object, which has no field API
        $user = user_load($account->id());
      }
      else ($user == $account);
      $my_exchanges = referenced_exchanges($user);
      if ($type == 'mcapi_exchange') {
        $exchanges = array($owner);
      }
      else {
        $exchanges = referenced_exchanges($owner);
      }
      foreach($exchanges as $exchange) {
        foreach ($my_exchanges as $my_exchange) {
          if ($owner->id() == $my_exchange->id()) {
            //the current user is in the same exchange as the current entity
            $access = TRUE;
            continue 2;
          }
        }
      }
    }
    //now check if the max wallets for this bundle has been reached
    if (isset($access)) {
      if (\Drupal::entityManager()->getStorageController('mcapi_wallet')->spare($owner)) {
        return AccessInterface::ALLOW;
      }
    }
    return  AccessInterface::DENY;
  }

}

