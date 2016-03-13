<?php

/**
 * @file
 * Contains \Drupal\mcapi\Event\TransactionAssembleEvent.
 */

namespace Drupal\mcapi\Event;

use Drupal\mcapi\Entity\Transaction;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event which is fired before a new transaction is validated.
 *
 * @see \Drupal\mcapi\Entity\Transaction::assemble()
 */
class TransactionAssembleEvent extends GenericEvent {

  const EVENT_NAME = 'mcapi_transaction.assemble';//I think this is used by rules module

  /**
   *
   * @param Transaction $transaction
   */
  public function addChild(Transaction $transaction) {
    $this->getSubject()->children[] = $transaction;
  }

}
