<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\mcapi\Mcapi;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler for the name of the transaction state.
 *
 * I would hope for a generic filter to come along to render list key/values.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_type")
 */
class Type extends FieldPluginBase {

  private $types;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    // Is this needed?
    parent::init($view, $display, $options);
    $this->types = Mcapi::entityLabelList('mcapi_type');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $raw = $this->getValue($values);
    return ['#markup' => '<span class = "' . $raw . '">' . $this->types[$raw] . '</span>'];
  }

}
