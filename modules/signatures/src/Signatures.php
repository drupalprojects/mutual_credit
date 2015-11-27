<?php

/**
 * @file
 * Contains Drupal\mcapi_signatures\signatures
 */

namespace Drupal\mcapi_signatures;

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Type;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;

class Signatures {

  static function addSignature($transaction) {
    $relatives = Type::load($transaction->type->target_id)
      ->getThirdPartySetting('mcapi_signatures', 'signatures');
    $user_ids = \Drupal::service('mcapi.transaction_relative_manager')
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
        $transaction->set('state', TRANSACTION_STATE_FINISHED);
      }
    }
  }

}