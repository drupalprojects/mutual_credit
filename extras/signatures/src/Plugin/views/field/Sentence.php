<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\Sentence.
 */

namespace Drupal\mcapi_signatures\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\mcapi\Entity\Transaction;

/**
 *
 * @ViewsField("sentence")
  * NB Assumes that the serial field is added to the fields list in the view NOT using 'additional fields'
 */
class Sentence extends FieldPluginBase {

  function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return entity_view(
      Transaction::loadBySerials(array($values->serial)),
      'sentence'
    );
  }
}
