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

  /**
   * {@inheritdoc}
   * $ops are details, summary, payin, payout
   */
  public function checkAccess(EntityInterface $entity, $op, $langcode, AccountInterface $account) {

    //TODO tidy this up not sure what admin op is for
    if ($op == 'admin') {//this anticipates the limits module, which means we don't need a special access controller
      debug('accessing wallet with op: admin - why?');
      if ($account->hasPermission('manage mcapi')) {//we'll need a better way to check permission with groups
        return AccessResult::allowed()->cachePerRole();
      }
    }
    if ($account->hasPermission('manage mcapi')) {
      return AccessResult::allowed()->cachePerRole();
    }
    //case WALLET_ACCESS_OWNER
    elseif ($op == 'edit') {
      //edit isn't a configurable operation. Only the owner can do it
      $entity->access['edit'] = array($entity->user_id());
    }
    
    //case WALLET_ACCESS_USERS
    if (is_array($entity->access[$op])) {//designated users
      if(in_array($account->id(), $entity->access[$op])) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    switch ($entity->access[$op]) {
      //TODO move this to the exchanges module
      case WALLET_ACCESS_EXCHANGE:
        //check that the current user is the same exchange as the wallet
        if (array_intersect(array_keys(Exchanges::walletInExchanges($entity)), Exchanges::in(NULL, TRUE))) {
          return AccessResult::allowed()->cachePerUser();
        }
        break;
      case WALLET_ACCESS_AUTH:
        if ($account->id()) {
          return AccessResult::allowed()->cachePerUser();
        }
        break;
      case WALLET_ACCESS_ANY:
        return AccessResult::allowed()->cachePerRole();
      default:
        throw new \Exception('WalletAccessControlHanlder::checkAccess() does not know op: '.$op);
    }

    return AccessResult::forbidden()->cachePerRole();
  }

}
