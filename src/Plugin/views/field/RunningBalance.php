<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\RunningBalance.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to provide running balance for a given transaction
 * @note reads from the transaction index table
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("transaction_running_balance")
 */
class RunningBalance extends Worth {

  private $wallet_id;

  /**
   * {@inheritdoc}
   */
  public function query() {
    //all we need to do is determine the wallet id for which we want the balance
    //it could from one of two args, or a filter
    $this->addAdditionalFields();
    $arg_names = array_keys($this->view->argument);
    $arg_pos = array_search('held_wallet', $arg_names);
    if ($arg_pos === FALSE) {
      $arg_pos = array_search('wallet_id', $arg_names);
    }

    if ($arg_pos !== FALSE) {
      $this->wallet_id = $this->view->args[$arg_pos];
    }
    else {
      $arg_pos = array_search('wallet_id', array_keys($this->view->filter));
      if ($arg_pos !== FALSE)
        {$this->wallet_id = $this->view->filter['wallet_id']->value['value'];
      }
      else {
        drupal_set_message("Running balance requires filter or contextual filter 'Transaction index: wallet_id'", 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $worth_field = $this->getEntity($values)->worth;
    //something bizarre is happening here. sometimes the entity associated with
    //this row has an empty worth value. but it works if we reload it
    if (!$worth_field->getValue()) {
      drupal_set_message('reloading transaction '.$values->serial, 'warning');
      $worth_field = \Drupal\mcapi\Entity\Transaction::load($values->serial)
        ->worth;
      if (!$worth_field) {
        drupal_set_message('failed to reload '.$values->serial, 'error');
        return array();
      }
    }
    $vals = [];
    foreach ($worth_field->currencies() as $curr_id) {
      $raw = \Drupal::entityTypeManager()->getStorage('mcapi_transaction')->runningBalance(
        $values->wallet_id,
        $curr_id,
        $values->xid,
        'xid'
      );
      $vals[] = ['curr_id' => $curr_id, 'value' => $raw];
    }
    $worth_field->setValue($vals);
    $options = [
      'label' => 'hidden',
      'settings' => [
        'format' => $this->options['format']
      ]
    ];
    if (property_exists($values, 'curr_id')) {
      $options['settings']['curr_ids'] = [$values->curr_id];
    }
    return $worth_field->view($options);
  }

}
