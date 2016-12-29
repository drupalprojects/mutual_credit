<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

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

}

