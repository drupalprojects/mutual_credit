<?php

namespace Drupal\mcapi_limits\Event;

use \Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event which is fired whenever the limits constraint prevents a transactions.
 */
class TransactionPreventedEvent extends GenericEvent {

  const EVENT_NAME = 'mcapi_transaction.prevented';

}
