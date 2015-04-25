<?php

/**
 * @file
 * Contains \Drupal\mcapi\McapiEvents.
 */

namespace Drupal\mcapi;

/**
 * Contains all events for creating transactions and transitioning them between states
 * 
 * @todo rename this to TransactionEvents?
 */
final class McapiEvents {

  /**
   * The name of the event triggered when a transaction is validated.
   *
   * This event allows modules to react at two stages of the transaction being
   * saved. 
   *
   * @Event
   *
   * @var string
   */
  
  
  /**
   * This hook is good for taxes
   */
  const CHILDREN = 'mcapi.transaction.children';
  
  /**
   * This hook is good for triggering notifications
   */
  const TRANSITION = 'mcapi.transaction.transition';

}
