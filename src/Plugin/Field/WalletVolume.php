<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;

/**
 * A computed field adding upt the transaction volume of a wallet.
 */
class WalletVolume extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    return $this->getEntity()->getStatAll('volume');
  }

}

