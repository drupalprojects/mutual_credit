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
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Exchanges;


/**
 * Defines an access controller for adding new wallets to entity types
 */
class WalletAddAccessCheck implements AccessInterface {

  /**
   * Wallets can only be added if:
   *  the current user has permission to create wallets in the exchange of that entity
   *  or
   *  the current user is on their own page and have permission to create own wallets
   * and
   *  the max wallets threshhold is not reached for that entity
   *
   * @param Route $account
   * @param RouteMatchInterface $account
   * @param AccountInterface $account
   *
   * @return AccessInterface constant
   *   a constant either ALLOW, DENY, or KILL
   *
   * @todo work out how to do this now that the $request isn't passed
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $config = \Drupal::config('mcapi.wallets');
    //this fetches the entity we are viewing - the would-be owner of the wallet we would add
    $params = $route_match->getParameters()->all();
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
    elseif ($account->hasPermission('manage mcapi')) {
      if (Wallet::spare($owner)) {
        $user = is_a($account, 'Drupal\user\Entity\User') ? $account : User::load($account->id());
        if (array_intersect(array_keys(Exchanges::in($user, TRUE)), Exchanges::in($owner, TRUE))) {
          //the current manager-user is in the same exchange as the current entity
          return AccessResult::allowed()->cachePerUser();
        }
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }

}

