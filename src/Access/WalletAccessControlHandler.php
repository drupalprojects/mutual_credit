<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\WalletAccessControlHandler.
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Exchanges;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class WalletAccessControlHandler extends EntityAccessControlHandler {
  
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $config = \Drupal::config('mcapi.wallets');
    //this fetches the entity we are viewing - the would-be owner of the wallet we would add
    $params = \Drupal::routeMatch()->getParameters()->all();
    $owner = reset($params);
    if (!is_object($owner)) {
      $owner = entity_load(key($params), reset($params));
    }
    $max = $config->get('entity_types.'.$owner->getEntityTypeId() .':'. $owner->bundle());
    
    //for users to add their own wallets
    if ($account->hasPermission('create own wallets') && $owner->getEntityTypeId() == 'user' && $account->id() == $owner->id) {
      $checkroom = TRUE;
    }
    //for exchange managers to add wallets to any entity of that exchange
    elseif ($account->hasPermission('manage mcapi')) {
      $checkroom = TRUE;
    }
    if (isset($checkroom)) {
      if (wallet_room($owner)) {
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

    if ($result = $this->initialChecks($entity, $op, $account)) return $result;
    
    switch ($entity->access[$op]) {
      case WALLET_ACCESS_AUTH:
        if ($account->id()) {
          return AccessResult::allowed()->cachePerUser();
        }
        break;
      case WALLET_ACCESS_ANY:
        return AccessResult::allowed()->cachePerRole();
      default:
        return AccessResult::neutral()->cachePerRole();
    }
  }
  
  function initialChecks($entity, $op, $account) {

    if ($account->hasPermission('manage mcapi')) {
      return AccessResult::allowed()->cachePerRole();
    }
    //case WALLET_ACCESS_OWNER
    elseif ($op == 'edit') {
      //edit isn't a configurable operation. Only the owner can do it
      $entity->access['edit'] = array($entity->user_id());
    }
    
    //special case WALLET_ACCESS_USERS where $op is an array
    if (is_array($entity->access[$op])) {//designated users
      if(in_array($account->id(), $entity->access[$op])) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
  }
}
