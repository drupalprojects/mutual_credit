<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\DifferentWalletsConstraintValidator.
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class DifferentWalletsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    //check the payer and payee aren't the same
    if ($transaction->payer->target_id == $transaction->payee->target_id) {
      $this->context
        ->buildViolation($constraint->message)
        ->atPath('payer.0')
        ->addViolation();
    }
  }
}
