<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\ExchangeMembers.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to get the member count from the exchange entity
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("exchange_members")
 */
class ExchangeMembers extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    //$this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->getEntity($values)->members();
  }

}
