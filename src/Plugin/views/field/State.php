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

  function query() {
    parent::query();
    $this->states = mcapi_entity_label_list('mcapi_state');
  }

  function render(ResultRow $values) {
    return $this->states[$this->getValue($values)];
  }

}
