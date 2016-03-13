<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\TransactionSubscriber.
 * @note not used in this module. just here for demonstration purposes
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi_limits\Event\TransactionPreventedEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *
 */
class TransactionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      McapiLimitsEvents::PREVENTED => ['onTransactionPrevented']
    ];
  }

  /**
   *
   * @param TransactionPreventedEvent $event
   *   no ->arguments() are passed. getSubject() gives the transaction
   * @param string $eventName which is always mcapi_transaction.assemble
   * @param ContainerAwareEventDispatcher $container
   */
  function onTransactionPrevented(TransactionPreventedEvent $event, $eventName, ContainerAwareEventDispatcher $container) {
    drupal_set_message('onTransactionPrevented: '.$eventName);//testing
  }


}
