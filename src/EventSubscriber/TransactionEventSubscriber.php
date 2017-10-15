<?php

namespace Drupal\mcapi\EventSubscriber;

use Drupal\mcapi\Event\McapiEvents;
use Drupal\mcapi\Event\TransactionSaveEvents;
use Drupal\mcapi\Event\TransactionAssembleEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hooks for saving a transaction.
 */
class TransactionEventSubscriber implements EventSubscriberInterface {

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
   * This is an example for now, but it mightMcapi work with rules later on.
   *
   * Use $events->addChild($transaction).
   *
   * @param TransactionAssembleEvent $event
   *   No ->arguments() are passed. getSubject() gives the transaction.
   * @param string $eventName
   *   Which is always mcapi_transaction.assemble.
   * @param ContainerAwareEventDispatcher $container
   *   The container.
   */
  public function onMakeChildren(TransactionAssembleEvent $event, $eventName, ContainerAwareEventDispatcher $container) {
    // drupal_set_message('onmakechildren: '.$eventName);//testing.
  }

  /**
   * Acts on a transaction and returns a render array in $events->output.
   *
   * @param TransactionSaveEvents $events
   *   $events->getArguments() yields form_values, old_state and operation name,
   *    or action. $events->getSubject() gives the transaction.
   * @param string $eventName
   *   The machine name of the event.
   * @param ContainerAwareEventDispatcher $container
   *   The container.
   */
  public function onTransactionAction(TransactionSaveEvents $events, $eventName, ContainerAwareEventDispatcher $container) {
    // $events->setMessage('onTransactionAction: '.$eventName);.
  }

}
