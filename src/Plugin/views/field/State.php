<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\State.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler for the name of the transaction state
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_state")
 */
class State extends FieldPluginBase {

  function render(ResultRow $values) {
    $this->states = mcapi_entity_label_list('mcapi_state');
    return $this->states[$this->getValue($values)];
  }

}
