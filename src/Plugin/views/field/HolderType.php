<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\HolderType.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to link the transaction description to the transaction itself
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_holder_type")
 */
class HolderType extends Standard {

  private $labels;

  function __construct() {
    $defs = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($defs as $id => $def) {
      $this->labels[$id] = $def->getLabel();
    }
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    return $this->labels[$values->{$this->field_alias}];
  }

}
