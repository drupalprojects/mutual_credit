<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletBalance.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide running balance for a given transaction
 * @note reads from the transaction index table
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("transaction_running_balance")
 */
class WalletBalance extends FieldPluginBase {

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
    $worths = $this->getEntity($values)->getWorths();
    
  }

}
