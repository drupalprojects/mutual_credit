<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Entity\Currency;

/**
 * A computed field showing the wallet balance
 */
class WalletIncome extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    return $this->getEntity()->getStatAll('gross_in');
  }

  public function __toString() {
    $worth = $this->getValue();
    $currency = Currency::load($worth[0]['curr_id']);
    return (string)$currency->format($worth[0]['value'], CurrencyInterface::DISPLAY_NORMAL, FALSE);
  }

}

