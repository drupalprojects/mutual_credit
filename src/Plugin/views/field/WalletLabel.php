<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletLabel.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\Component\Annotation\PluginID;
use Drupal\mcapi\Entity\Wallet;

/**
 * Virtual field handler to show the wallet's name
 * @todo make the wallet link into an option
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_wallet_label")
 */
class WalletLabel extends Standard {


  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $wallet = $this->getEntity($values);
    //TODO wait until https://www.drupal.org/node/2320989 is fixed
    if (!$wallet) return 'Bug in D8 alpha14';
    return l($wallet->label(), $wallet->url('canonical'));
  }

}
