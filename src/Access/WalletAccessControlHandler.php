<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\WalletAccessControlHandler.
 *
 * Wallets have 5 permissions each. payin, payout, view, and manage,
 * users should be able to determine the level of privacy on each wallet
 * Levels of privacy are the owner, the exchange, the site members, the public, and a special one which is specifically named users
 * Exchange administrators should be able to insist on some settings, e.g. any site member can payout of any wallet
 * User 1 should be able to restrict those options for exchange admins.
 * All that is fine, but then it needs to be stored in the db in such a way that we can build sql queries to check access, so that views respects it
 *
 * @todo is it possible to inject things into an existing service?
 *
 * @todo rework this now that details and summary are no longer wallet properties
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Mcapi;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class WalletAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    //this fetches the entity we are viewing - the would-be holder of the wallet we would add
    $params = \Drupal::routeMatch()->getParameters()->all();//I would expect routematch to have been injected here
    $holder_type = key($params);
    $holder = reset($params);
    //quickcheck for users to add their own wallets
    if ($account->hasPermission('create own wallets') && $holder_type == 'user' && $account->id() == $holder->id() or $account->hasPermission('manage mcapi')) {
      $owned_wallet_ids = \Drupal::entityTypeManager()
        ->getStorage('mcapi_wallet')->getQuery()
        ->condition('holder_entity_type', $holder_type)
        ->condition('holder_entity_id', $holder->id())
        ->execute();
      $max = Mcapi::maxWalletsOfBundle($holder_type, $holder->bundle());
      if (count($owned_wallet_ids) < $max) {
        return AccessResult::allowed()->addCacheableDependency($account);
      }
    }
    return AccessResult::forbidden()->addCacheableDependency($account);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $op, AccountInterface $account) {

    if ($account->hasPermission('manage mcapi')) {
      //includes user 1
      return AccessResult::allowed()->cachePerPermissions();
    }
    elseif ($op == 'update') {
      //ONLY the owner (always a user) can do it
      return AccessResult::allowedIf($entity->id() == $entity->getOwner()->id())->cachePerUser();
    }
    elseif ($op == 'view'){
      //there might need to be an intermediate option, for groups
      return AccessResult::allowedIfhasPermission($account, 'view all wallets')
        ->orif(
            AccessResult::allowedIf($entity->getOwnerId() == $account->id())
        )
        ->cachePerUser();
    }
    elseif($op == 'delete') {
      return AccessResult::allowedIf(!Mcapi::walletIsUsed($entity->id()))->addCacheTags(['mcapi_wallet:'.$entity->id()]);
      //can only delete if there are no transactions.
      //@todo maybe create a basefield unused flag rather than reading ledger
    }
    else mtrace();
  }

}
