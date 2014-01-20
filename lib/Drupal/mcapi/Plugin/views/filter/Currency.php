<?php

/**
 * @file
 * Definition of Drupal\node\Plugins\views\filter\Currency.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter by currency code.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("currcode")
 */
class Currency extends InOperator {

  public function getValueOptions() {
    $this->value_options = mcapi_currency_list(TRUE);
  }


  public function query($use_groupby = FALSE) {
    $table = $this->ensureMyTable();
    $this->query->addWhere(0, "$table.currcode", $this->options['value']);
  }
}
