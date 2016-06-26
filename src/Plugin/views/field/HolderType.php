<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;

/**
 * Field handler to link the transaction description to the transaction itself.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_holder_type")
 */
class HolderType extends Standard {

  private $labels;

  /**
   * Constructor.
   */
  public function __construct() {
    $defs = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($defs as $id => $def) {
      $this->labels[$id] = $def->getLabel();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->labels[$values->{$this->field_alias}];
  }

}
