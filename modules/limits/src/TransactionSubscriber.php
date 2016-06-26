<?php

namespace Drupal\mcapi_limits;

use Drupal\mcapi_limits\Event\TransactionPreventedEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A hook to check the limits.
 *
 * @deprecated in favour of entity field constraints
 */
class TransactionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      McapiLimitsEvents::PREVENTED => ['onTransactionPrevented'],
    ];
  }

  /**
   * Event.
   *
   * @param TransactionPreventedEvent $event
   *   No ->arguments() are passed. getSubject() gives the transaction.
   * @param string $eventName
   *   Which is always mcapi_transaction.assemble.
   * @param ContainerAwareEventDispatcher $container
   *   The container.
   */
  public function onTransactionPrevented(TransactionPreventedEvent $event, $eventName, ContainerAwareEventDispatcher $container) {

  }

}
