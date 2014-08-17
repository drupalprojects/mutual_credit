<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Worth.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * When we look at transaction index table, we need to view one worth at a time
 * deriving the currency from a separate field
 *
 *
 * @todo This handler should use entities directly.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("worth")
 */
class Worth extends FieldPluginBase {

  /**
   * This field is only used on the mcapi_transactions_index table.
   * The transaction table is assumed to be rendered by the FieldAPI and WorthFieldItemList
   * @todo aggregation overrides this handler. But it should be resolved
   * @see Drupal\views\Plugin\ViewsHandlerManager where it says 'this is crazy'!
   */
  public function render(ResultRow $values) {
    //we have to guess the alias coz I don't know where to find it
    if ($curr_id = $values->mcapi_transactions_index_curr_id) {
      $currency = mcapi_currency_load($curr_id);
    }
    else throw new \Exception('Problem rendering worth field');//not sure when this would happen or what to do
    return $currency->format($this->getValue($values));
  }

}