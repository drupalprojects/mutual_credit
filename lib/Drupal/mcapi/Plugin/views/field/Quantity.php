<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Quantity
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to show the quantity of the transaction, without the currency
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("quantity")
 */
class Quantity extends FieldPluginBase {

  public function query($use_groupby = FALSE) {
    $table_alias = $this->ensureMyTable();
    $this->query->addField($table_alias, 'quantity', 'mcapi_transactions_worths_quantity');
  }

  function render(ResultRow $values) {
    return$values->mcapi_transactions_worths_quantity;
  }

}
