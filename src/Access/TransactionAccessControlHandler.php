<?php

namespace Drupal\mcapi\Access;

/**
 * @file
 * Contains \Drupal\mcapi\Access\TransactionAccessControlHandler.
 */

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\mcapi\Mcapi;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Creating transactions is simply a matter of being allowed to pay in and
    // out of at least 1 wallet.
    return Mcapi::enoughWallets($account->id()) ?
      AccessResult::Allowed()->cachePerUser() :
      AccessResult::Forbidden()->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation === 'view' and $account->hasPermission('view all transactions')) {
      // @todo URGENT. Handle the named payees and payers
      return $return_as_object ? AccessResult::allowed()->cachePerUser() : TRUE;
    }
    return Mcapi::transactionActionLoad($operation)
      ->getPlugin()
      ->access($transaction, $account, $return_as_object)
      ->cachePerUser();
  }

}
