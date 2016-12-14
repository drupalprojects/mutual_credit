<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * The SQL storage entity query class.
 */
class WalletQuery extends BaseQuery {

  public function prepare() {
    $this->condition('orphaned', 0);
    return parent::prepare();
  }
}
