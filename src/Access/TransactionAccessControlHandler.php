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
use Drupal\mcapi\Entity\Wallet;
use Drupal\system\Entity\Action;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessControlHandler extends EntityAccessControlHandler {

  private $routeMatch;
  static $result;

  public function __construct() {
    $this->routeMatch = \Drupal::RouteMatch();
  }
  
  
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    die('transaction checkCreateAccess');
  }
  
  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $operation, AccountInterface $account = NULL, $return_as_object = false) {
    if ($operation == 'view') {
      //you can view a transaction if you can view either the payer OR payee wallets
      $bool = $transaction->payer->entity->access('details', $account)
      || $transaction->payee->entity->access('details', $account);
      if ($return_as_object) {
        return $bool ? AccessResult::allowed()->cachePerUser() : AccessResult::forbidden()->cachePerUser();
      }
      else return $bool;
    }
    elseif ($operation == 'update') {
      $allRelatives = \Drupal::Service('mcapi.transaction_relative_manager')->activePlugins();
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
    return mcapi_transaction_action_load($operation)
      ->getPlugin()
      ->access($transaction, $account, TRUE)
      ->cachePerUser();
  }


  /**
   * router access callback
   * Find out if the user has access to enough wallets to be able to transact
   *
   * @return AccessResult
   */
  public static function enoughWallets($account = NULL) {
    if (!Self::$result) {
      if (!$account){
        $account = \Drupal::currentUser();
      }
      $walletStorage = \Drupal::entityManager()->getStorage('mcapi_wallet');
      $payin = $walletStorage->walletsUserCanActOn(Wallet::OP_PAYIN, $account);
      $payout = $walletStorage->walletsUserCanActOn(Wallet::OP_PAYOUT, $account);
      if (empty($payin) || empty($payout)) {
        //this is deliberately ambiguous as to whether you have no wallets or the system has no other wallets.
        drupal_set_message('There are no wallets for you to trade with', 'warning');
        Self::$result = AccessResult::forbidden()->cachePerUser();
      }
      else {
        //there must be at least one wallet in each (and they must be different!)
        Self::$result = (count(array_unique(array_merge($payin, $payout))) > 1) ?
          AccessResult::allowed()->cachePerUser() :
          AccessResult::forbidden()->cachePerUser();
      }
    }
    return Self::$result;
  }

}
