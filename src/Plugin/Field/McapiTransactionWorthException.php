<?php

/*
 * @file
 * Definition of Drupal\mcapi\McapiException.
 */

namespace Drupal\mcapi\Plugin\Field;

use Drupal\mcapi\McapiTransactionException;

/**
 * Base class for all exceptions thrown by Community Accounting functions.
 *
 * This class has no functionality of its own other than allowing all
 * Field API exceptions to be caught by a single catch block.
 */
class McapiTransactionWorthException extends McapiTransactionException {

  protected $currency;

  public function __construct($curr_id = 0, $message = 'Unknown error on worths field') {

    $this->field = 'worths';
    if ($curr_id) {
      $this->currency = entity_load('mcapi_currency', $curr_id);
      $this->field .= ']['.$this->currency->id();
    }
    $this->message = $message;
  }

}
