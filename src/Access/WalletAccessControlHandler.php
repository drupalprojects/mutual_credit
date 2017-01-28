<?php

namespace Drupal\mcapi\Access;

use Drupal\mcapi\Entity\WalletInterface;
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
    $this->prepareUser($account);
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
    $this->prepareUser($account);
    if ($account->hasPermission('manage mcapi')) {
      if ($op == 'delete') {
        // Don't cache
        return AccessResult::allowedIf($entity->isVirgin());
      }
      // Includes user 1.
      return AccessResult::allowed()->cachePerPermissions();
    }
    elseif ($op == 'view') {
      return AccessResult::allowedIfhasPermission($account, 'view all transactions')
        ->cachePerPermissions();
    }
    elseif ($this->controlsWallet($entity, $account)) {
      if ($op == 'delete') {
        // Don't cache
        return AccessResult::allowedIf($entity->isVirgin());
      }
      return AccessResult::allowed()->cachePerUser();
    }
    elseif ($op == 'transactions') {
drupal_set_message('just checking wallet.transactions access op is being used');
      if ($account->hasPermission('view all transactions')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      elseif ($entity->public->value == TRUE) {
        return AccessResult::allowed()->addCacheableDependency($entity);
      }
      return AccessResult::neutral()->addCacheableDependency($entity);
    }
  }

  /**
   * Check if the given entity either owns or is burser of the given wallet.
   *
   * @param WalletInterface $wallet
   * @param AccountInterface $account
   *
   * @return boolean
   *   TRUE if the $account controls the $entity
   */
  private function controlsWallet(WalletInterface $wallet, AccountInterface $account) {
    $uid = $account->id();
    if ($wallet->getOwnerId() == $uid) {
      return TRUE;
    }
    foreach ($wallet->bursers->getValue() as $item) {
      if ($item['target_id'] == $uid) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
