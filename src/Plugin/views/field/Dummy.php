<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to link the transaction description to the transaction itself.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_dummy")
 */
class Dummy extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
     $this->addAdditionalFields();
  }

  public function render(ResultRow $row, $field = NULL) {
    $entity = $this->getEntity($row);
    // McNasty with the fieldname!
    $fieldname = substr($this->getField(), 1);
    return $entity->{$fieldname}->getValue();
  }

}
