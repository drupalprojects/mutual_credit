<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * A computed field showing the wallet balance
 */
class WalletOutgoing extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    return $this->getEntity()->getStatAll('gross_out');
  }


  public function __toString() {
    $worth = $this->getValue();
    $currency = Currency::load($worth[0]['curr_id']);
    return (string)$currency->format($worth[0]['value'], Currency::DISPLAY_NORMAL, FALSE);
  }
}

