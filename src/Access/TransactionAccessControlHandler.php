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
    return Mcapi::enoughWallets($account->id()) ?
      AccessResult::Allowed()->cachePerUser() :
      AccessResult::Forbidden()->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $operation, AccountInterface $account = NULL, $return_as_object = false) {
    //note at the moment the update permission is not supported
    if ($operation === 'view' or $operation === 'update') {
      $bool = FALSE;
      //@todo URGENT. Also if you are named as a payee or payer on the wallet
      if ($operation === 'view' and $account->hasPermission('view all transactions')) {
         $bool = TRUE;
      }
      else {
        if (is_null($account)) {
          $account = \Drupal::currentUser();
        }
        $bool = Mcapi::transactionRelatives(\Drupal::config('mcapi.settings')->get($operation))
          ->isRelative($transaction, $account);
      }
      if (!$return_as_object) {
        return $bool;
      }
      return $bool ? AccessResult::allowed()->cachePerUser() :
        AccessResult::forbidden()->cachePerUser();
    }
    return Mcapi::transactionActionLoad($operation)
      ->getPlugin()
      ->access($transaction, $account, TRUE)
      ->cachePerUser();
  }

}
