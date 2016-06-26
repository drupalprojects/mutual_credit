<?php

namespace Drupal\mcapi_limits;

/**
 * Contains all events relating to transaction limits.
 */
final class McapiLimitsEvents {

  /**
   * The name of the event triggered when a transaction is prevented.
   *
   * @Event
   *
   * @var string
   */

  const PREVENTED = 'mcapi_transaction.prevented';

}
