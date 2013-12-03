<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Link.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("mcapi_state")
 */
class State extends FieldPluginBase {

  function render(ResultRow $values) {

    $this->states = mcapi_get_states('#options');
    return $this->states[$values->{$this->field_alias}];
  }

}