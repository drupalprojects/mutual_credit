<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * The SQL storage entity query class.
 *
 * Extended to handle new methods to filter by wallets and wallet holders
 */
class TransactionQuery extends BaseQuery {

  private $involving;
  private $payer;
  private $payee;

  /**
   * Filter the entityQuery to include only transactions in which the given
   * entity is payer or payee
   *
   * @param ContentEntityInterface $entity
   * @return \Drupal\mcapi\Entity\TransactionQuery
   */
  public function involving(ContentEntityInterface $entity) {
    if ($entity->getEntityTypeId() == 'mcapi_wallet') {
      // Work directly on the transaction table and wallet ids
      $this->orConditionGroup()
        ->condition('payer', (array) $value, 'IN')
        ->condition('payee', (array) $value, 'IN');
    }
    else {
      // deal with this during the compile phase when we can add joins
      $this->involving = $entity;
    }
    return $this;
  }

  /**
   * Filter the entityQuery to include only transactions in which the given
   * entity is payer
   *
   * @param ContentEntityInterface $entity
   * @return \Drupal\mcapi\Entity\TransactionQuery
   */
  protected function payer(ContentEntityInterface $entity) {
    if ($entity->getEntityTypeId() == 'mcapi_wallet') {
      $this->condition('payer', $entity->id());
    }
    else {
      $this->payer = $entity;
    }
    return $this;
  }

  /**
   * Filter the entityQuery to include only transactions in which the given
   * entity is payee
   *
   * @param ContentEntityInterface $entity
   * @return \Drupal\mcapi\Entity\TransactionQuery
   */
  protected function payee(ContentEntityInterface $entity) {
    if ($entity->getEntityTypeId() == 'mcapi_wallet') {
      $this->condition('payee', $entity->id());
    }
    else {
      $this->payee = $entity;
    }
    return $this;
  }

  protected function compile() {
    parent::compile();
    if ($this->involving) {// This is the wallet holder.
      $this->sqlQuery->join('mcapi_wallet', 'payerw', 'base_table.payer = payerw.wid');
      $this->sqlQuery->join('mcapi_wallet', 'payeew', 'base_table.payee = payeew.wid');
      $entity_id = $this->involving->id();
      $this->sqlQuery->condition('payerw.holder_entity_type', 'user');
      $this->sqlQuery->condition('payeew.holder_entity_type', $this->involving->getEntityTypeId());
      $this->sqlQuery->condition(
        $this->sqlQuery->orConditionGroup()
          ->condition('payeew.holder_entity_id', $entity_id)
          ->condition('payerw.holder_entity_id', $entity_id)
      );
    }
    elseif ($this->payer) {

    }
    elseif ($this->payee) {

    }
    return $this;
  }

}


