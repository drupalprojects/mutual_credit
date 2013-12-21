<?php

/*
 * @file
 * Definition of Drupal\mcapi\McapiException.
 */

namespace Drupal\mcapi;

/**
 * Base class for all exceptions thrown by Community Accounting functions.
 *
 * This class has no functionality of its own other than allowing all
 * Field API exceptions to be caught by a single catch block.
 */
class McapiTransactionException extends \RuntimeException { 

  public $field; //any of the fields in the mcapi_transactions table, or 'worths'

  public function __construct($fieldname, $message) {
    $this->field = $fieldname;
    $this->message = $message;
  }

}
