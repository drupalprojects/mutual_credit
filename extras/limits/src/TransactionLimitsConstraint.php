<?php

namespace Drupal\mcapi_limits;

/**
 * @file
 * Contains \Drupal\mcapi_limits\TransactionLimitsConstraint.
 *
 * takes the transaction and makes a projection of the sum of these plus all 
 * saved transactions in a positive state against the balance limits for each 
 * affected account,
 * NB. this event is only run when a transaction is inserted:
 * It only checks against transactions in a POSITIVE state 
 * i.e. counted transaction.
 * 
 * @todo find out if there is any advantage to just using the parent and remove if not
 * 
 */

use Symfony\Component\Validator\Constraint;

class TransactionLimitsConstraint extends Constraint {
  
  public $message = 'this needs to be overwritten...';
  
  function __construct($options = null) {
    debug($options, "Need to try to rewrite these incoming options?");
    parent::__construct($options);
  }
    
}
