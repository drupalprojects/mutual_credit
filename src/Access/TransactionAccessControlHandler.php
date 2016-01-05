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
    if ($operation == 'view') {
      //you can view a transaction if you can view either the payer OR payee wallets
      //todo I think this needs deleting - we don't have such sophisticated access control yet
      //$bool = $transaction->payer->entity->access('viewlog', $account)
      //|| $transaction->payee->entity->access('view', $account);
      
      $bool = $account->hasPermission('view all transactions') ||
          $account->id() == $transaction->payer->entity->getOwnerId() ||
          $account->id() == $transaction->payee->entity->getOwnerId();
          
      if ($return_as_object) {
        return $bool ? 
          AccessResult::allowed()->cachePerUser() : 
          AccessResult::forbidden()->cachePerUser();
      }
      else {
        return $bool;
      }
    }
    elseif ($operation == 'update') {
      $allRelatives = Mcapi::transactionRelatives()->activePlugins();
      foreach ($transaction->type->entity->edit as $relative) {
        if (is_null($account)) {
          $account = \Drupal::currentUser();
        }
        //check if the $account is this relative to the transaction
        if ($allRelatives[$relative]->isRelative($transaction, $account)) {
          return $return_as_object ? AccessResult::allowed()->cachePerUser() : TRUE;
        }
      }
      return $return_as_object ? AccessResult::forbidden()->cachePerUser() : FALSE;
    }
    return Mcapi::transactionActionLoad($operation)
      ->getPlugin()
      ->access($transaction, $account, TRUE)
      ->cachePerUser();
  }

}
