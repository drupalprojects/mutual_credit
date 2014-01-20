<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Undo
 *
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;

/**
 * Undo operation
 *
 * @Operation(
 *   id = "undo",
 *   label = @Translation("Undo"),
 *   description = @Translation("Undo, according to global undo mode"),
 *   settings = {
 *     "weight" = "3",
 *     "sure" = "Are you sure you want to undo?"
 *   }
 * )
 */
class Undo extends OperationBase {

  /**
   * {@inheritdoc}
  */
  public function access_form(CurrencyInterface $currency) {
    //return the access functions for each transaction state
    $element = parent::access_form($currency);
    foreach (mcapi_get_states() as $state) {
      $elements[$constantVal] = $element;
      $elements[$constantVal]['#title'] = $state->label;
      $elements[$constantVal]['#description'] = $state->description;
      $elements[$constantVal]['#default_value'] = $currency->access_undo[$state->value];
    }
  }

  /**
   *  access callback for transaction operation 'view'
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->state->value == TRANSACTION_STATE_UNDONE) RETURN FALSE;
    $access_plugins = transaction_access_plugins();
    //see the comments in OperationBase
    foreach ($transaction->worths[0] as $worth) {
      foreach (@$worth->currency->access_undo[$transaction->state->value] as $plugin) {
        if ($access_plugins[$plugin]->checkAccess($transaction)) continue 2;
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {
    $transaction->delete();
    $message = t('The transaction is undone.') .' ';
    return array('#markup' => $message);
  }

}
