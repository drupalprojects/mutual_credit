<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * The SQL storage entity query class.
 */
class TransactionQuery extends BaseQuery {

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = NULL, $langcode = NULL) {
    // Need to add some special conditions.
    if ($property == 'involving') {
      $group = $this->orConditionGroup()
        ->condition('payer', (array) $value, 'IN')
        ->condition('payee', (array) $value, 'IN');
      $this->condition($group);
    }
    else {
      $this->condition->condition($property, $value, $operator);
    }
    return $this;
  }
}
