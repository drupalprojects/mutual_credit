<?php

/**
 * @file
 * Contains \Drupal\mcapi\McapiEvents.
 */

namespace Drupal\mcapi;

/**
 * Contains all events for creating transactions and transitioning them between states
 */
final class McapiEvents {

  /**
   * The name of the event triggered when a transaction is validated.
   *
   * This event allows modules to react to a new entity type being created. The
   * event listener method receives a \Drupal\Mcapi\TransactionEvent
   * instance.
   *
   * @Event
   *
   * @see \Drupal\Core\Entity\EntityTypeEvent
   * @see \Drupal\Core\Entity\EntityManager::onEntityTypeCreate()
   * @see \Drupal\Core\Entity\EntityTypeEventSubscriberTrait
   * @see \Drupal\views\EventSubscriber\ViewsEntitySchemaSubscriber::onEntityTypeCreate()
   *
   * @var string
   */
  const VALIDATE = 'mcapi.transaction.validate';


}
