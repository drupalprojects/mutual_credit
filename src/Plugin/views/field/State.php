<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\State.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\mcapi\Mcapi;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler for the name of the transaction state
 * I would hope for a generic filter to come along to render list key/values
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_state")
 */
class State extends FieldPluginBase {

  private $states;

  function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);//is this needed?
    $this->states = Mcapi::entityLabelList('mcapi_state');
  }

  function render(ResultRow $values) {
    $raw = $this->getValue($values);
    //should be translated
    return ['#markup' => '<span class = "mcapi-state-'.$raw.'">'.$this->states[$raw].'</span>'];
  }

}
