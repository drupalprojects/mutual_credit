<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletLabel.
 *
 * @deprecated
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\EntityLabel;
use Drupal\mcapi\Entity\Wallet;

/**
 * Field handler for the name of the transaction state
 * I would hope for a generic filter to come along to render list key/values
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wallet_label")
 */
class WalletLabel extends EntityLabel {

  /**
   * {@inheritDoc}
   */
  public function preRender(&$values) {
    //this is just to avoid the parent function which has some wierd expectations
  }

  public function render(ResultRow $values) {
    $wid = $this->getValue($values);
    $wallet = Wallet::load($wid);
    if (!empty($this->options['link_to_entity'])) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['url'] = $wallet->urlInfo();
    }
    return $this->sanitizeValue($wallet->label());
  }

}
