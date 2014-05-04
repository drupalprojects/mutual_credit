<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\SignOff
 *  @todo This needs to be finished. Might want to inherit some things from the Sign operation
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Sign Off operation
 *
 * @Operation(
 *   id = "sign_off",
 *   label = @Translation("Sign off"),
 *   description = @Translation("Sign a pending transaction on behalf of all pending signatories"),
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Are you sure you want to finalise this transaction?"
 *   }
 * )
 */
class SignOff extends OperationBase {

  /*
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {

    foreach ($transaction->signatures as $uid => $signed) {
      if ($signed) continue;
      transaction_sign($transaction, user_load($uid));
    }
    return array(
      '#markup' => t(
        '@transaction is signed off',
        array('@transaction' => $transaction->label())
      )
    );
  }

  /*
   * {@inheritdoc}
   */
  public function opAccess(TransactionInterface $transaction) {
    //@todo this operation needs some settings...
    return FALSE;
    if ($transaction->get('state')->value == TRANSACTION_STATE_PENDING) {

    }
  }

  public function settingsForm(array &$form) {
    parent::settingsForm($form);

  }

}
