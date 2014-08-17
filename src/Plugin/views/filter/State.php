<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugins\views\filter\State.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter by transaction state
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("mcapi_state")
 */
class State extends InOperator {

  public function getValueOptions() {
    $this->value_options = mcapi_entity_label_list('mcapi_state');
  }

  public function defineOptions() {
    $options = parent::defineOptions();
    foreach (\Drupal::config('mcapi.misc')->get('counted') as $key => $val) {
      if ($val) $options['value']['default'][$key] = $key;
    }
    return $options;
  }

}
