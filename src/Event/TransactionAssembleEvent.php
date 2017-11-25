<?php

namespace Drupal\mcapi\Event;

use Drupal\mcapi\Entity\Transaction;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event which is fired before a new transaction is validated.
 *
 * @see \Drupal\mcapi\Entity\Transaction::assemble()
 */
class TransactionAssembleEvent extends GenericEvent {

  // I think this is used by rules module.
  const EVENT_NAME = 'mcapi_transaction.assemble';

  /**
   * Add a child transaction.
   *
   * @param Transaction $transaction
   *   The new child transaction.
   */
  public function addChild(Transaction $transaction) {
    $this->getSubject()->children[] = $transaction;
  }

  /**
   * Get the parent transaction.
   * 
   * @return Transaction
   */
  public function getTransaction() {
    return $this->getSubject();
  }


}
