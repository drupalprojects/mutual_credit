<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * A computed field counting all the transactions in a wallet.
 */
class WalletPartners extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $sum = 0;
    foreach ($this->getEntity()->getSummaries() as $curr) {
      $sum += $curr['partners'];
    }
    return $sum;

  }

}

