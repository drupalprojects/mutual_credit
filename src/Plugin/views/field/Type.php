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

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $raw = $this->getValue($values);

    $label = \Drupal\mcapi\Entity\Type::load($raw)->label();
    return ['#markup' => '<span class = "mcapi-type-'. $raw .'">' . $label . '</span>'];
  }

}
