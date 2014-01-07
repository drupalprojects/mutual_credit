<?php

/**
 * @file
 * Definition of Drupal\node\Plugins\views\filter\Status.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter by transaction state
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("mcapi_state")
 */
class State extends InOperator {

  public function getValueOptions() {
    $this->value_options = mcapi_get_states(TRUE);
  }

}
