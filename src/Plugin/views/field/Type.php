<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Type.
 */

namespace Drupal\mcapi\Plugin\views\field;

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
 * @ViewsField("mcapi_Type")
 */
class Type extends FieldPluginBase {

  private $types;

  function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);//is this needed?
    $this->types = mcapi_entity_label_list('mcapi_type');
  }

  function render(ResultRow $values) {
    return $this->types[$this->getValue($values)];
  }

}
