<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Field\FieldItemList;

/**
 * A computed field showing the wallet balance in all currencies.
 */
class WalletBalance extends FieldItemList {



  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    return $this->getEntity()->getStatAll('balance');
  }

  public function __toString() {
    $worth = $this->getValue();
    $currency = Currency::load($worth[0]['curr_id']);
    return (string)$currency->format($worth[0]['value'], CurrencyInterface::DISPLAY_NORMAL, FALSE);
  }

}

