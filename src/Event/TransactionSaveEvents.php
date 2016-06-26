<?php

namespace Drupal\mcapi\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event which is fired on any transaction operation.
 *
 * @see \Drupal\mcapi\Entity\Transaction::assemble()
 */
class TransactionSaveEvents extends GenericEvent {

  // I think this is used by rules module.
  const EVENT_NAME = 'mcapi_transaction_save';

  private $messages = [];

  /**
   * Store a message with this transaction.
   *
   * @param string $markup
   *   The message to be set.
   * @param string $type
   *   The type of message i.e. status, warning or error.
   */
  public function setMessage($markup, $type = 'status') {
    if (strlen($markup)) {
      $this->messages[$type][] = $markup;
    }
  }

  /**
   * Retrieve the messages associated with this event.
   *
   * @return array
   *   renderable array
   */
  public function getMessages() {
    return $this->messages;
  }

}
