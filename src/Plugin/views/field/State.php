<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\State.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler for the name of the transaction state
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_state")
 */
class State extends FieldPluginBase {

  private $states;

  function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);//is this needed?
    $this->states = mcapi_entity_label_list('mcapi_state');
  }

  function render(ResultRow $values) {
    return $this->states[$this->getValue($values)];
  }

}
