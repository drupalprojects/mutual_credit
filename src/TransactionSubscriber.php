<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionSubscriber.
 * @note not used in this module. just here for demonstration purposes
 */

namespace Drupal\mcapi;

use Drupal\mcapi\Event\TransactionSaveEvents;
use Drupal\mcapi\Event\TransactionAssembleEvent;
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
      McapiEvents::ASSEMBLE => ['onmakeChildren'],
      McapiEvents::ACTION => ['onTransactionAction']
    ];
  }

  /**
   *
   * This is an example for now, but it mightMcapi work with rules later on.
   * use $events->addChild($transaction)
   *
   * @param TransactionAssembleEvent $event
   *   no ->arguments() are passed. getSubject() gives the transaction
   * @param string $eventName which is always mcapi_transaction.assemble
   * @param ContainerAwareEventDispatcher $container
   */
  function onMakeChildren(TransactionAssembleEvent $event, $eventName, ContainerAwareEventDispatcher $container) {
    //drupal_set_message('onmakechildren: '.$eventName);//testing
  }

  /**
   * Does things with a transactions and returns a render array in $events->output
   *
   * @param TransactionSaveEvents $events
   *   $events->getArguments() yields form_values, old_state and operation name, or action
   *   $events->getSubject() gives the transaction
   * @param string $eventName
   * @param ContainerAwareEventDispatcher $container
   */
  function onTransactionAction(TransactionSaveEvents $events, $eventName, ContainerAwareEventDispatcher $container) {
    //$events->setMessage('onTransactionAction: '.$eventName);
  }

}
