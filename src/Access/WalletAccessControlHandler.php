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
   * $ops are list, summary, pay, charge, admin
   */
  public function checkAccess(EntityInterface $entity, $op, $langcode, AccountInterface $account) {

    if ($account->hasPermission('manage mcapi')) {
      return AccessResult::allowed()->cachePerRole();
    }
    //edit isn't a configurable operation. Only the owner can do it
    if ($op == 'edit') {
      $entity->access['edit'] = array($entity->user_id());
    }
    if ($op == 'admin') {//this anticipates the limits module, which means we don't need a special access controller
      if ($account->hasPermission('manage mcapi')) {//we'll need a better way to check permission with groups
        return AccessResult::allowed()->cachePerRole();
      }
    }

    if (is_array($entity->access[$op])) {//designated users
      if(in_array($account->id(), $entity->access[$op])) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    switch ($entity->access[$op]) {
      case WALLET_ACCESS_EXCHANGE:
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
