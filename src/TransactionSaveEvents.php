<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionSaveEvents.
 * @todo consider replacing with Symfony\Component\EventDispatcher\GenericEvent
 */

namespace Drupal\mcapi;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Defines a base class for all entity type events.
 */
class TransactionSaveEvents extends GenericEvent {

  /**
   * same as parent::getSubject();
   * @return type
   */
  public function getTransaction() {
    return $this->getSubject();
  }

}
