<?php

/**
 * @file
 * Contains Drupal\mcapi_signatures\signatures
 */

namespace Drupal\mcapi_signatures;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\Type;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Mcapi;

class Signatures {

  static function addSignature($transaction) {
    $relatives = Type::load($transaction->type->target_id)
      ->getThirdPartySetting('mcapi_signatures', 'signatures');
    $user_ids = Mcapi::transactionRelatives()
      ->getUsers($transaction, $relatives);
    foreach ($user_ids as $uid) {
      $transaction->signatures[$uid] = 0;
    }
    //sign for the current user
    Self::sign($transaction, \Drupal::currentUser());
  }

  /**
   * sign a transaction
   * change the state if no more signatures are left
   * would be nice if this was in a decorator class so $transaction->sign($account) is possible
   * @param TransactionInterface $transaction
   * @param AccountInterface $account
   */
  static function sign(TransactionInterface $transaction, AccountInterface $account) {
    if (array_key_exists($account->id(), $transaction->signatures)) {
      $transaction->signatures[$account->id()] = REQUEST_TIME;
      //set the state to finished if there are no outstanding signatures
      if (array_search(0, $transaction->signatures) === FALSE) {
        $transaction->set('state', 'done');
      }
    }
  }

  /**
   * @param integer $uid
   * @return string[]
   *   serial numbers of the transactions
   */
  static function transactionsNeedingSigOfUser($uid) {
    //assumes data integrity that all transactions referenced are in pending state
    return \Drupal::database()->select("mcapi_signatures", 's')
      ->fields('s', array('serial'))
      ->condition('uid', $uid)
      ->condition('signed', '')
      ->execute()
      ->fetchCol();
  }
}