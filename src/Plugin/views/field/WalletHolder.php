<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletHolder.
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
 * @ViewsField("mcapi_wallet_holder")
 */
class WalletHolder extends Standard {


  /**
   * {@inheritdoc}
   */
  public function query() {
    //$this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    return $this->getEntity($values)->getHolder()->link();

  }

}
