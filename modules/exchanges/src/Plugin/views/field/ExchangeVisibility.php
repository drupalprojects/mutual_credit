<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Plugin\views\field\ExchangeVisibility.
 */

namespace Drupal\mcapi_exchanges\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for the name of the privacy setting
 * TODO isn't this just a boolean?
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("exchange_visibility")
 */
class ExchangeVisibility extends FieldPluginBase {

  function render(ResultRow $values) {
    return $this->getEntity($values)->visibility_options($this->getValue($values));
  }

}
