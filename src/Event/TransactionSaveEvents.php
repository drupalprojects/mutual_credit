<?php

/**
 * @file
 * Contains \Drupal\mcapi\Event\TransactionSaveEvents.
 */

namespace Drupal\mcapi\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event which is fired on any transaction operation
 *
 * @see \Drupal\mcapi\Entity\Transaction::assemble()
 */
class TransactionSaveEvents extends GenericEvent {

  const EVENT_NAME = 'mcapi_transaction_save';//I think this is used by rules module

  private $messages = [];

  /**
   *
   * @param string $markup
   * @param string $type
   *   the type of message i.e. status, warning or error
   */
  public function setMessage($string, $type = 'status') {
    if (strlen($string)) {
      $this->messages[$type][] = $string;
    }
  }

  /**
   *
   * @return array
   *   renderable array
   */
  public function getMessages() {
    return $this->messages;
  }

}
