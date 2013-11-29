<?php

/**
 * @file
 * Definition of Drupal\node\Plugins\views\filter\Currency.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\field\FieldPluginBase;


/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("currcode")
 */
class Currency extends InOperator {

  public function getValueOptions() {
    $this->value_options = mcapi_currency_list();
  }


  public function query($use_groupby = FALSE) {
    $this->join('mcapi_transactions_worths', 'w', 'w.xid = mcapi_transactions.xid');
    $this->query->addWhere(0, 'currcode', $this->options['value']);
  }
}
