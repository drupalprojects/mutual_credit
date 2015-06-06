<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionSubscriber.
 */

namespace Drupal\mcapi;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\TransactionSaveEvents;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher ;

/**
 *
 */
class TransactionSubscriber implements EventSubscriberInterface {

  //we could inject into __construct() using services.yml

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      McapiEvents::CHILDREN => ['onmakeChildren'],
      McapiEvents::TRANSITION => ['onTransactionTransition']
    ];
  }

  /**
   *
   * This is an example for now, but it mightMcapiEvents work with rules later on.
   * use $events->addChild(Transaction)
   *
   * @param TransactionSaveEvents $events
   *   no ->arguments() are passed. getSubject() gives the transaction
   * @param string $eventName
   * @param ContainerAwareEventDispatcher $container
   */
  function onMakeChildren(TransactionSaveEvents $events, $eventName, ContainerAwareEventDispatcher $container) {
    //could $eventName be anything but 'mcapi.transaction.children' a.k.a. McapiEvents::CHILDREN
    //what to do with the $container?
    $transaction = $events->getSubject();
  }

  /**
   * Does things with a transactions and returns a render array in $events->output
   *
   * @param TransactionSaveEvents $events
   *   $events->getArguments() yields form_values, old_state and transition
   *   $events->getSubject() gives the transaction
   * @param string $eventName
   * @param ContainerAwareEventDispatcher $container
   */
  function onTransactionTransition(TransactionSaveEvents $events, $eventName, ContainerAwareEventDispatcher $container) {
    $events->addMessage('onTransactionTransition');
  }

}
