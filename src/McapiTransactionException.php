<?php

/*
 * @file
 * Definition of Drupal\mcapi\McapiTransactionException.
 */

namespace Drupal\mcapi;

/**
 * Base class for all exceptions thrown by Community Accounting functions.
 *
 * This class has no functionality of its own other than allowing all
 * Field API exceptions to be caught by a single catch block.
 */
class McapiTransactionException extends \RuntimeException {

  protected $field; //array path towards the form element

  public function __construct($fieldname, $message) {
    $this->field = $fieldname;
    $this->message = $message;
  }

  public function getField() {
    return $this->field;
  }
}
