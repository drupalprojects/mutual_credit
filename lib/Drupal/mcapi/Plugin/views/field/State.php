<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\State.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to the name of the transaction state
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("mcapi_state")
 */
class State extends FieldPluginBase {

  function render(ResultRow $values) {

    $this->states = mcapi_get_states(TRUE);
    return $this->states[$values->{$this->field_alias}];
  }

}
