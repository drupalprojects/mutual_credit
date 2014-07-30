<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletLabel.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;
use Drupal\mcapi\Entity\Wallet;

/**
 * Field handler to link the transaction description to the transaction itself
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_wallet_label")
 */
class WalletLabel extends Standard {


  /**
   * {@inheritdoc}
   */
  public function __query() {
    //$this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $wid = $this->getValue($values);
    return Wallet::load($wid)->label();
  }

}
