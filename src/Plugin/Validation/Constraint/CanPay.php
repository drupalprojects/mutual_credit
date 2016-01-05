<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\CanPay.
 * Combined Constraint / Constraintvalidator
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

abstract class CanPay extends Constraint implements ConstraintValidatorInterface {

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;


  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    //don't have a separate class for the validator, use this one.
    return get_class($this);
  }

}
