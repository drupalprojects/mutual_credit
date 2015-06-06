<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionSaveEvents.
 *
 * Seems to be used for two completely different things
 */

namespace Drupal\mcapi;

use Symfony\Component\EventDispatcher\GenericEvent;
use Drupal\mcapi\Entity\Transaction;

/**
 * Defines a base class for all entity type events.
 */
class TransactionSaveEvents extends GenericEvent {

  private $children = [];
  private $messages = [];

  /**
   *
   * @param Transaction $transaction
   */
  public function addChild(Transaction $transaction) {
    $this->getSubject()->children[] = $transaction;
  }

  /**
   *
   * @param string $markup
   */
  public function addMessage($string) {
    $this->messages[] = $string;
  }

  /**
   *
   * @return array
   *   renderable array
   */
  public function getMessage() {
    return implode(' ', $this->messages);
  }

}
