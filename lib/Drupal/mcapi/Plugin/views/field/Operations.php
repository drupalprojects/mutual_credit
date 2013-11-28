<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Operations
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("transaction_operations")
 */
class Operations extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  function render(ResultRow $values) {
    $transaction = $this->getEntity($values);
    $links = array();
    foreach (transaction_get_links($transaction, '', TRUE) as $link) {
      $links[] = drupal_render($link);
    }

      return print_r($links, 1);
    return $links;
  }

}
