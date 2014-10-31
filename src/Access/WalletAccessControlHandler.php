<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\WalletAccessControlHandler.
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Entity\Exchange;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class WalletAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   * $ops are list, summary, pay, charge
   */
  public function checkAccess(EntityInterface $entity, $op, $langcode, AccountInterface $account) {

    if ($account->hasPermission('manage mcapi')) return AccessResult::allowed()->cacheperRole();
    //edit isn't a configurable operation. Only the owner can do it
    if ($op == 'edit') {
      $entity->access['edit'] = array($entity->user_id());
    }

    if (is_array($entity->access[$op])) {//designated users
      if(in_array($account->id(), $entity->access[$op])) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    switch ($entity->access[$op]) {
      case WALLET_ACCESS_EXCHANGE:
        if (array_intersect_key($entity->in_exchanges(), Exchange::referenced_exchanges(NULL, TRUE))) {
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
