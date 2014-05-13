<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugins\views\filter\Status.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("mcapi_type")
 */
class Type extends InOperator {


  public function getValueOptions() {
    $this->value_options = mcapi_get_types(TRUE);
  }

}
