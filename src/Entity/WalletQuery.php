<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * The SQL storage entity query class.
 */
class WalletQuery extends BaseQuery {

  public function prepare() {
    // Skip orphaned wallets
    $this->condition('holder_entity_type', '', '<>');
    // Skip system wallets
    //$this->condition('system', '0');

    return parent::prepare();


  }

}
