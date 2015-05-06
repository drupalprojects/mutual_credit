<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\WalletAccessControlHandler.
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class WalletAccessControlHandler extends EntityAccessControlHandler {

  /**
   * This is likely to be checked twice so may need caching
   * @param type $entity_bundle
   * @param AccountInterface $account
   * @param array $context
   * @param type $return_as_object
   *
   * @return AccessResult
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    //this fetches the entity we are viewing - the would-be owner of the wallet we would add
    $params = \Drupal::routeMatch()->getParameters()->all();//I would expect routematch to have been injected here
    $owner = reset($params);
    if (!is_object($owner)) {
      $owner = entity_load(key($params), reset($params));
    }
    //quickcheck for users to add their own wallets
    if ($account->hasPermission('create own wallets') && $owner->getEntityTypeId() == 'user' && $account->id() == $owner->id) {
      $checkroom = TRUE;
    }
    //for exchange managers to add wallets to any entity of that exchange
    elseif ($account->hasPermission('manage mcapi')) {
      $checkroom = TRUE;
    }
    if (isset($checkroom)) {
      if ($this->walletRoom($owner)) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }

  /**
   * {@inheritdoc}
   * $ops are details, summary, payin, payout
   */
  public function checkAccess(EntityInterface $entity, $op, $langcode, AccountInterface $account) {
    if ($result = $this->initialChecks($entity, $op, $account)) {
      return $result;
    }
    switch ($entity->access[$op]) {
      case WALLET_ACCESS_AUTH:
        if ($account->id()) {
          return AccessResult::allowed()->cachePerUser();
        }
        break;
      case WALLET_ACCESS_ANY:
        return AccessResult::allowed()->cachePerPermissions();
      default:
        return AccessResult::neutral()->cachePerPermissions();
    }
  }


  /**
   * grant access to administrators, or for editing, or for designated users
   */
  function initialChecks($entity, $op, $account) {

    if ($account->hasPermission('manage mcapi')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    //case WALLET_ACCESS_OWNER
    elseif ($op == 'edit') {
      //edit isn't a configurable operation. Only the owner can do it
      $entity->access['edit'] = array($entity->ownerUserId());
    }

    //special case WALLET_ACCESS_USERS where $op is an array
    if (is_array($entity->access[$op])) {//designated users
      if(in_array($account->id(), $entity->access[$op])) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
  }

  /**
   * determine whether a wallet can be added
   *
   * @return Boolean
   *   TRUE if the $owner has less than the max wallets
   */
  function walletRoom($owner) {
    $config = \Drupal::config('mcapi.wallets');
    $entity_type = $owner->getEntityTypeId();
    $bundle = $entity_type.':'.$owner->bundle();
    $max = $config->get('entity_types.'. $bundle);
    //quick check first for this common scenario
    if ($entity_type == 'user' && $max == 1 && $config->get('autoadd_name')) {
      return TRUE;
    }
    else {
      $owned_wallets = \Drupal\mcapi\Storage\WalletStorage::getOwnedIds($owner);
      return count($owned_wallets) < $max;
    }
  }
}
