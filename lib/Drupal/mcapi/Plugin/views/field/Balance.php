<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Balance.
 */

namespace Drupal\mcapi\Plugin\views\field;

//TODO which of these are actually needed?
use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to provide running balance for a given transaction
 * in the index table
 *
 * @todo This handler should use entities directly.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("balance")
 */
class Balance extends FieldPluginBase {

  var $uid1;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
    //we can't do this much earlier because the view->argument isn't there yet
    $arg = array_search('uid1', array_keys($this->view->argument));
    if (is_numeric($arg)) {
      $this->uid1 = $this->view->args[$arg];
    }
    elseif (isset($this->view->filter['uid1'])) {
      $this->uid1 = $this->view->filter['uid1']->value['value'];
    }
    else {
      drupal_set_message("Running balance requires filter or contextual filter 'Transaction index: 1st user'", 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $currcode = &$values->{$this->aliases['currcode']};

    $transaction = $this->getEntity($values);
    //the running balance depends the order of the transactions
    //order or creation is not enough if they were created at the same time
    //created date is not necessarily unique per transaction, so needs a secondary sort.
    $quantity = db_query(
      "SELECT SUM(diff) FROM {mcapi_transactions_index}
        WHERE uid1 = :uid1
        AND created <= :created
        AND xid <= :xid
        AND currcode = :currcode",
      array(
        ':created' => $transaction->created->value,
        ':uid1' => $this->uid1,
        ':xid' => $transaction->xid->value,
        ':currcode' => $currcode
      )
    )->fetchField();
    //TODO I'm not sure how this is supposed to work...
    return entity_load('mcapi_currency', $currcode)->format($quantity);
  }

}