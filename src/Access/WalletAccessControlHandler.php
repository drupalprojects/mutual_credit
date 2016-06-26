<?php

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\mcapi\Mcapi;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 *
 * @todo inject $routematch
 */
class WalletAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    // This fetches the entity we are viewing - the would-be holder of the
    // wallet we would add.
    $params = \Drupal::routeMatch()->getParameters()->all();
    $holder_type = key($params);
    $holder = reset($params);
    // Quickcheck for users to add their own wallets.
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
  protected function checkAccess(EntityInterface $entity, $op, AccountInterface $account) {
    if ($account->hasPermission('manage mcapi')) {
      // Includes user 1.
      return AccessResult::allowed()->cachePerPermissions();
    }
    elseif ($op == 'update') {
      // ONLY the owner (always a user) can do it.
      return AccessResult::allowedIf($entity->id() == $entity->getOwner()->id())->cachePerUser();
    }
    elseif ($op == 'view') {
      // There might need to be an intermediate option, for groups.
      return AccessResult::allowedIfhasPermission($account, 'view all transactions')
        ->orif(
          AccessResult::allowedIf($entity->getOwnerId() == $account->id())
        )
        ->cachePerUser();
    }
    elseif ($op == 'delete') {
      return AccessResult::allowedIf(!$entity->isUsed())->addCacheTags(['mcapi_wallet:' . $entity->id()]);
      // Can only delete wallet if there are no transactions.
      // @todo maybe create a basefield unused flag rather than reading ledger
    }
    elseif ($op == 'transactions') {
      if ($account->hasPermission('view all transactions') or $entity->payways->value == Wallet::PAYWAY_AUTO) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      elseif ($entity->public->value == TRUE) {
        return AccessResult::allowed()->addCacheableDependency($entity);
      }
      // Named users on each wallet must be able to see transactions.
      // @todo these need testing.
      elseif ($entity->payways->value == Wallet::PAYWAY_ANYONE_IN) {
        foreach ($entity->payers->referencedEntities() as $user) {
          if ($user->id() == $account->id()) {
            return AccessResult::allowed()->addCacheableDependency($entity);
          }
        }
      }
      elseif ($entity->payways->value == Wallet::PAYWAY_ANYONE_OUT) {
        foreach ($entity->payees->referencedEntities() as $user) {
          if ($user->id() == $account->id()) {
            return AccessResult::allowed()->addCacheableDependency($entity);
          }
        }
      }
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
  }

}
