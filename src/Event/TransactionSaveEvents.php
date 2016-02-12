<?php

/**
 * @file
 * Contains \Drupal\mcapi\Event\TransactionSaveEvents.
 */

namespace Drupal\mcapi\Event;

use Symfony\Component\EventDispatcher\GenericEvent;
use Drupal\mcapi\Entity\Transaction;

class TransactionSaveEvents extends GenericEvent {

  private $children = [];
  private $messages = [];

  const EVENT_NAME = 'mcapi_transaction_save';

  /**
   *
   * @param Transaction $transaction
   */
  public function addChild(Transaction $transaction) {
    dsm('TransactionSaveEvents::addChild');
    $this->getSubject()->children[] = $transaction;
  }

  /**
   *
   * @param string $markup
   */
  public function addMessage($string) {
    dsm('TransactionSaveEvents::addMessage');
    $this->messages[] = $string;
  }

  /**
   *
   * @return array
   *   renderable array
   */
  public function getMessage() {
    dsm('TransactionSaveEvents::getMessage');
    return implode(' ', $this->messages);
  }

}
