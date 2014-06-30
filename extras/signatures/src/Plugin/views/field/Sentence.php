<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\Sentence.
 */

namespace Drupal\mcapi_signatures\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 *
 * @ViewsField("sentence")
  * NB Assumes that the serial field is added to the fields list in the view NOT using 'additional fields'
 */
class Sentence extends FieldPluginBase {

  function query() {
    //parent::query();
    //$this->addAdditionalFields();
    //$this->ensureMyTable();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $transaction = mcapi_transaction_load_by_serial($values->serial);
    return entity_view($transaction, 'sentence');
  }
}
