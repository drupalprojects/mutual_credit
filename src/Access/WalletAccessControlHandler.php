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
use Drupal\mcapi\Entity\Wallet;

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
    //this fetches the entity we are viewing - the would-be holder of the wallet we would add
    $params = \Drupal::routeMatch()->getParameters()->all();//I would expect routematch to have been injected here
    $holder = reset($params);
    if (!is_object($holder)) {
      $holder = entity_load(key($params), reset($params));
    }
    //quickcheck for users to add their own wallets
    if ($account->hasPermission('create own wallets') and $holder->getEntityTypeId() == 'user' and $account->id() == $holder->id()) {
      $checkroom = TRUE;
    }
    //for exchange managers to add wallets to any entity of that exchange
    elseif ($account->hasPermission('manage mcapi')) {
      $checkroom = TRUE;
    }
    if (isset($checkroom)) {
      if ($this->walletRoom($holder)) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }

  /**
   * {@inheritdoc}
   * @see Wallet constants for the $ops
   *
   */
  public function checkAccess(EntityInterface $entity, $op, AccountInterface $account) {
    if ($result = $this->initialChecks($entity, $op, $account)) {
      return $result;
    }
    switch ($entity->access[$op]) {
      case Wallet::ACCESS_AUTH:
        if ($account->id()) {
          return AccessResult::allowed()->cachePerUser();
        }
        break;
      case Wallet::ACCESS_ANY:
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
    //case Wallet::ACCESS_OWNER
    elseif ($op == 'edit') {
      //edit isn't a configurable operation. Only the holder can do it
      $entity->access['edit'] = array($entity->getHolder()->id());
    }

    //special case Wallet::ACCESS_USERS where $op is an array
    if (is_array($entity->access[$op])) {//designated users
      if(in_array($account->id(), $entity->access[$op])) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
  }

  /**
   * determine whether a holder can accept another wallet
   * 
   * @param ContentEntityInterface $holder
   *   The entity holding the wallet
   *
   * @return boolean
   *   TRUE if the $holder has less than the max wallets
   */
  function walletRoom($holder) {
    $config = \Drupal::config('mcapi.settings');
    $entity_type = $holder->getEntityTypeId();
    //quick check first for this common scenario
    if ($entity_type == 'user' && mcapi_one_wallet_per_user_mode() && $config->get('autoadd_name')) {
      return TRUE;
    }
    else {
      $maxes = $config->get('entity_types)');
      $owned_wallets = \Drupal::entityManager()
        ->getStorage('mcapi_wallet')
        ->filter(['holder' => $holder]);
      return count($owned_wallets) < $maxes[$entity_type.':'.$holder->bundle()];
    }
  }
}
