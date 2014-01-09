<?php

/**
 * @file
 * Definition of Drupal\mcapi\WorthCurrency
 */

namespace Drupal\mcapi;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;

class WorthCurrency extends TypedData {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $field = $this->getParent();

    if (!empty($field->currcode)) {
      return entity_load('mcapi_currency', $field->currcode);
    }
  }

}