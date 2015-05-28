<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionSubscriber.
 */

namespace Drupal\mcapi;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mcapi\Entity\Transaction;

/**
 *
 */
class TransactionSubscriber implements EventSubscriberInterface {

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
   * @throws McapiException
   */
  function onMakeChildren() {
    //debug('running hook '.McapiEvents::CHILDREN);
  }

  /**
   * @throws McapiException
   */
  function onTransactionTransition() {
    debug('running hook '.McapiEvents::TRANSITION);
  }

}
