<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\WalletAddAccessCheck.
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\RouteMatch;
use Drupal\mcapi\Entity\Exchange;


/**
 * Defines an access controller for adding new wallets to entity types
 */
class WalletAddAccessCheck implements AccessInterface {

  private $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    $routes = [];
    $types = \Drupal::config('mcapi.wallets')->get('entity_types');
    foreach((array)$types as $entity_bundle => $max) {
      if (!$max) continue;
      list($entity_type, $bundle) = explode(':', $entity_bundle);
      $routes[] = "mcapi.wallet.add.$bundle";
    }
    return $routes;
  }

  /**
   * Wallets can only be added if:
   *  the current user has permission to create wallets in the exchange of that entity
   *  or
   *  the current user is on their own page and have permission to create own wallets
   * and
   *  the max wallets threshhold is not reached for that entity
   *
   * @param AccountInterface $account
   *
   * @return AccessInterface constant
   *   a constant either ALLOW, DENY, or KILL
   *
   * @todo work out how to do this now that the $request isn't passed
   */
  public function access(AccountInterface $account) {
   return AccessResult::allowed();


    $config = \Drupal::config('mcapi.wallets');
    //this fetches the entity we are viewing - the would-be owner of the wallet we would add
    $params = RouteMatch::createFromRequest($request)->getParameters()->all();
    list($entity_type, $id) = each($params);
    $owner = \Drupal::EntityManager()->getStorage($entity_type)->load($id);
    $max = $config->get('entity_types.'.$entity_type);

    //quick check first for this common scenario
    if ($entity_type == 'user' && $max == 1 && $config->get('autoadd_name')) {
      //the max of 1 wallet was autoadded when the wallet was created
      return AccessResult::forbidden()->cachePerRole();
    }
    //for users to add their own wallets
    if ($account->hasPermission('create own wallets') && $entity_type == 'user' && $account->id() == $owner->id) {
      return AccessResult::allowed()->cachePerRole();
    }
    //for exchange managers to add wallets to any entity of that exchange
    elseif ($account->hasPermission('manage own exchanges')) {
      //is there a better way to
      if(!is_a($account, 'Drupal\user\Entity\User')) {
        //that means we've been passed a userSession object, which has no field API
        $user = User::load($account->id());
      }
      else ($user = $account);
      $my_exchanges = Exchange::referenced_exchanges($user, TRUE);
      if ($entity_type == 'mcapi_exchange') {
        $exchanges = array($owner);
      }
      else {
        $exchanges = Exchange::referenced_exchanges($owner, TRUE);
      }
      foreach($exchanges as $exchange) {
        foreach ($my_exchanges as $my_exchange) {
          if ($owner->id() == $my_exchange->id()) {
            //the current manager-user is in the same exchange as the current entity
            //TODO inject the entityManager
            if (\Drupal::entityManager()->getStorage('mcapi_wallet')->spare($owner)) {
              return AccessResult::allowed()->cachePerUser();
            }


          }
        }
      }
    }
    return AccessResult::forbidden()->cachePerUser;
  }

}

