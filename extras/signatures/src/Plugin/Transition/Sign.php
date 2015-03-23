<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Plugin\Transition\Sign
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Transition;

use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Sign transition
 *
 * @Transition(
 *   id = "sign",
 *   label = @Translation("Sign"),
 *   description = @Translation("Sign a pending transaction"),
 *   module = "mcapi_sgnatures",
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Are you sure you want to sign?"
 *   }
 * )
 */
class Sign extends TransitionBase {

  /*
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {
    module_load_include('inc', 'mcapi_signatures');
    transaction_sign($transaction, \Drupal::currentUser());

    if ($transaction->state->target_id == TRANSACTION_STATE_FINISHED) {
      $message = t('@transaction is signed off', array('@transaction' => $transaction->label()));
    }
    else{
      $num = 0;
      foreach ($transaction->signatures as $timestamp) {
        if (!$timestamp) $num++;
      }
      $message = \Drupal::Translation()->formatPlural($num, '1 signature remaining', '@count signatures remaining');
    }

    parent::execute($transaction, $values);
    $transaction->save();

    return array('#markup' => $message);
  }

  /*
   * {@inheritdoc}
  */
  public function opAccess(TransactionInterface $transaction, AccountInterface $account) {
    //Only the designated users can sign transactions, and
    if ($transaction->state->target_id == TRANSACTION_STATE_PENDING //the transaction is pending
      && isset($transaction->signatures) && is_array($transaction->signatures)// signatures property is populated
      && array_key_exists(\Drupal::currentUser()->id(), $transaction->signatures)//the current user is a signatory
      && $transaction->signatures[\Drupal::currentUser()->id()] == 0//the curreny user hasn't signed
    ) return TRUE;
    return FALSE;
  }

}
