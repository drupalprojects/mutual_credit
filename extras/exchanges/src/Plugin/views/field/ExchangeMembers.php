<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Plugin\views\field\ExchangeMembers.
 */

namespace Drupal\mcapi_exchanges\Plugin\views\field;

use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to get the member count from the exchange entity
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("exchange_members")
 */
class ExchangeMembers extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $ids = $this->getEntity($values)->users();
    return count($ids);
  }

}
