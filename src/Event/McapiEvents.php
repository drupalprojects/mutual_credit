<?php

namespace Drupal\mcapi\Event;

/**
 * Contains all events for transitioning transactions between workflow states.
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
   * This hook is good for taxes.
   */
  const ASSEMBLE = 'mcapi_transaction.assemble';

  /**
   * This hook is good for triggering notifications.
   */
  const ACTION = 'mcapi_transaction.action';

}
