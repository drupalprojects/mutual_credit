<?php

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Base class for validating whether wallets can be used in certain directions.
 */
abstract class CanPay extends Constraint implements ConstraintValidatorInterface {

  /**
   * The Execution context.
   *
   * Used for adding Violations.
   *
   * @var ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    // don't have a separate class for the validator, use this one.
    return get_class($this);
  }

  /**
   * Add a violation to the context.
   *
   * @param string $message
   *   The violation message.
   * @param array $args
   *   The translation tokens for the message.
   */
  protected function addViolation($message, $args) {
    $this->context->addViolation($message, $args);
  }

}
