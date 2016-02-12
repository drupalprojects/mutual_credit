<?php

/**
 * @file
 * Contains \Drupal\mcapi\Event\TransactionAssembleEvent.
 */

namespace Drupal\mcapi\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event that is fired before a new transaction is validated.
 *
 * @see mcapi_transaction_assemble()
 */
class TransactionAssembleEvent extends GenericEvent {

  const EVENT_NAME = 'mcapi_transaction_assemble';

  /**
   *
   * @param Transaction $transaction
   */
  public function addChild(Transaction $transaction) {
    dsm('TransactionAssembleEvent::addChild');
    $this->getSubject()->children[] = $transaction;
  }

  /**
   *
   * @param string $markup
   */
  public function addMessage($string) {
    dsm('TransactionAssembleEvent::addMessage');
    $this->messages[] = $string;
  }

  /**
   *
   * @return array
   *   renderable array
   */
  public function getMessage() {
    dsm('TransactionAssembleEvent::getMessage');
    return implode(' ', $this->messages);
  }
  
}
