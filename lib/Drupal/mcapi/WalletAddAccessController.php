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
   * the max wallets threshhold is not reached for that entity
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
    //mdump($request->attributes->all());
    $owner = wallet_get_params($request);
    $type = $owner->entityType();

    //quick check first for this common scenario
    if ($type == 'user' && $config->get('entity_types.user') == 1 && $config->get('autoadd')) {
      return AccessInterface::DENY;
    }

    if ($account->hasPermission('create own wallets') && $type == 'user' && $account->id() == $owner->id) {
      $access = TRUE;
    }
    elseif ($account->hasPermission('manage own exchanges')) {
      $my_exchanges = user_exchanges($account);
      if ($type == 'exchange') {
        $exchanges = array($owner);
      }
      else {
        //what exchanges is this entity in?
        $fieldname = get_exchange_entity_fieldnames($type);
        //isn't there a proper way to get a nice array of entities out of an entityreference field?
        foreach ($owner->get($fieldname)->getValue() as $item) {
          $exchanges[] = entity_load('mcapi_exchange', $item['target_id']);
        }
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
    if ($access) {
      //finally we check the number of wallets already owned against the max for this entity type
      $already = db_select('mcapi_wallets');
      $already->addExpression('COUNT(wid)');
      $already->condition('pid', $owner->id())->condition('entity_type', $type)
      ->execute()->fetchField();
      if ($already < $config->get('types.'.$type)) return AccessInterface::ALLOW;
    }
    return  AccessInterface::DENY;
  }

}
