<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Balance.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide running balance for a given transaction
 * @note reads from the transaction index table
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("balance")
 */
class Balance extends FieldPluginBase {

  private $wallet_id;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
    //we can't do this much earlier because the view->argument isn't there yet
    $arg = array_search('wallet_id', array_keys($this->view->argument));
    if (is_numeric($arg)) {
      $this->wallet_id = $this->view->args[$arg];
    }
    elseif (isset($this->view->filter['wallet_id'])) {
      $this->wallet_id = $this->view->filter['wallet_id']->value['value'];
    }
    else {
      drupal_set_message("Running balance requires filter or contextual filter 'Transaction index: wallet_id'", 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $transaction = $this->getEntity($values);
    $worth_field = $transaction->worth;
    foreach ($worth_field->currencies() as $curr_id) {

      //the running balance depends the order of the transactions
      //we will assume the order of creation is what's wanted because that
      //corresponds to the order of the xid
      //note that it is possible to change the apparent creation date.
      $raw = \Drupal::entityManager()->getStorage('mcapi_transaction')->runningBalance(
        $this->wallet_id,
        $transaction->xid->value,
        $curr_id
      );
      $vals[] = ['curr_id' => $curr_id, 'value' => $raw];
    }
    $worth_field->setValue($vals);
    return $worth_field->view();
  }

}
