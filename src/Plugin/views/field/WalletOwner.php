<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletOwner.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to link the transaction description to the transaction itself
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_wallet_owner")
 */
class WalletOwner extends Standard {


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
    $owner = $this->getEntity($values)->getOwner();
    if ($url = $owner->url()) {
      return l($owner->label(), $url);
    }
    else {
      return \Drupal::Config('system.site')->get('name');
    }

  }

}
