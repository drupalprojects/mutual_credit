<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi\Entity\WalletInterface;

/**
 * The SQL storage entity query class.
 *
 * Extended to handle new methods to filter by wallets and wallet holders
 */
class TransactionQuery extends BaseQuery {

  /**
   * @var ContentEntityInterface A wallet holder
   */
  private $participant;

  /**
   * @var ContentEntityInterface A wallet holder
   */
  private $payer;

  /**
   * @var ContentEntityInterface A wallet holder
   */
  private $payee;

  /**
   * Filter the entityQuery to include only transactions in which the given
   * entity is payer or payee
   *
   * @param ContentEntityInterface $entity
   * @return \Drupal\mcapi\Entity\TransactionQuery
   */
  public function involving(ContentEntityInterface $entity) {
    if ($entity instanceOf WalletInterface) {
      // Work directly on the transaction table and wallet ids
      $group = $this->orConditionGroup()
        ->condition('payer', (array) $entity->id(), 'IN')
        ->condition('payee', (array) $entity->id(), 'IN');
      $this->condition($group);
    }
    else {
      // deal with this during the compile phase when we can add joins
      $this->participant = $entity;
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
    if ($entity instanceOf WalletInterface) {
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
    if ($entity instanceOf WalletInterface) {
      $this->condition('payee', $entity->id());
    }
    else {
      $this->payee = $entity;
    }
    return $this;
  }

  protected function compile() {
    parent::compile();
    if ($this->participant) {// This is the wallet holder.
      $this->sqlQuery->join('mcapi_wallet', 'payerw', 'base_table.payer = payerw.wid');
      $this->sqlQuery->join('mcapi_wallet', 'payeew', 'base_table.payee = payeew.wid');
      $entity_id = $this->participant->id();
      $this->sqlQuery->condition('payerw.holder_entity_type', 'user');
      $this->sqlQuery->condition('payeew.holder_entity_type', $this->participant->getEntityTypeId());
      $this->sqlQuery->condition(
        $this->sqlQuery->orConditionGroup()
          ->condition('payeew.holder_entity_id', $entity_id)
          ->condition('payerw.holder_entity_id', $entity_id)
      );
    }
    else {
      if ($this->payer) {
        $this->sqlQuery->join('mcapi_wallet', 'payerw', 'base_table.payer = payerw.wid');
        $this->sqlQuery->condition('payerw.holder_entity_type', 'user');
        $this->sqlQuery->condition('payeew.holder_entity_id', $this->payee->id());
      }
      if ($this->payee) {
        $this->sqlQuery->join('mcapi_wallet', 'payerw', 'base_table.payer = payerw.wid');
        $this->sqlQuery->condition('payerw.holder_entity_type', 'user');
        $this->sqlQuery->condition('payeew.holder_entity_id', $this->payee->id());
      }
    }
    return $this;
  }

  /**
   * Prepares the basic query with proper metadata/tags and base fields.
   *
   * @return \Drupal\Core\Entity\Query\Sql\Query
   *   Returns the called object.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   *   Thrown if the base table does not exist.
   */
  protected function prepare() {
    $this->sqlQuery = $this->connection->select('mcapi_transaction', 'base_table', ['conjunction' => $this->conjunction]);
    $this->sqlQuery->addMetaData('entity_type', 'mcapi_transaction');
    // Add the key field for fetchAllKeyed().
    $this->sqlFields["base_table.xid"] = ['base_table', 'xid'];

    // Now add the value column for fetchAllKeyed(), the serial number
    $this->sqlFields["base_table.serial"] = ['base_table', 'serial'];

    $this->sqlQuery->addTag('mcapi_transaction_access');
    $this->sqlQuery->addTag('entity_query');
    $this->sqlQuery->addTag('entity_query_mcapi_transaction');

    return $this;
  }

}

