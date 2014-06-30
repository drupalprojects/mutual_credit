<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Worth.
 */

namespace Drupal\mcapi\Plugin\views\field;

//TODO which of these are actually needed?
use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\field\Standard;

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
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    //@todo how do we get the alias for curr_id field, which was added as an 'additional field'
    //or even load up the currency as a
    return mcapi_currency_load($values->mcapi_transactions_index_curr_id)
      ->format($this->getValue($values));
  }

}