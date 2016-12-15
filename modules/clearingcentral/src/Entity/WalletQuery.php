<?php

namespace Drupal\mcapi_cc\Entity;

use Drupal\mcapi\Entity\WalletQuery as BaseQuery;

/**
 * The SQL storage entity query class.
 *
 * @deprecated 
 */
class WalletQuery extends BaseQuery {

  public function prepare() {
    // Exclude intertrading wallets
    //$this->condition('payways', Wallet::PAYWAY_AUTO, '<>');
    return parent::prepare();
  }
}
