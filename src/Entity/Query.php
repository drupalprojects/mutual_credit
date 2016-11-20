<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * The SQL storage entity query class.
 */
class Query extends BaseQuery {

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = NULL, $langcode = NULL) {
    // Need to add some special conditions.
    switch ($property) {
      case 'involving':
        $group = $this->orConditionGroup()
          ->condition('payer', (array) $value, 'IN')
          ->condition('payee', (array) $value, 'IN');
        $this->condition($group);
        break;

      default:
        // Copied from the parent.
        $this->condition->condition($property, $value, $operator);
    }

    return $this;
  }

  public function prepare() {
    parent::prepare();
    // Exclude intertrading or orphaned wallets
    if ($this->getEntityTypeId() == 'mcapi_wallet') {
      $this->condition('payways', Wallet::PAYWAY_AUTO, '<>');
      $this->condition('orphaned', 0);
    }
    return $this;
  }
}
