<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugins\views\filter\Currency.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter by currency code.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("curr_id")
 */
class Currency extends InOperator {

  public function getValueOptions() {
    $this->value_options = mcapi_entity_label_list(entity_load_multiple_by_property('mcapi_currency', array('status' => TRUE)));
  }


  public function query($use_groupby = FALSE) {
    $table = $this->ensureMyTable();
    $this->query->addWhere(0, "$table.curr_id", $this->options['value']);
  }
}
