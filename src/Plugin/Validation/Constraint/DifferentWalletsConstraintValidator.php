<?php

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Check that the two wallets in the transaction are different.
 *
 *@todo inject or remove logger
 */
class DifferentWalletsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    $payer = $transaction->payer->target_id;
    $payee = $transaction->payee->target_id;
    // Check the payer and payee aren't the same.
    if ($payer === $payee) {
      \Drupal::logger('mcapi')
        ->debug($payer . "!=" . $payee);
      $this->context
        ->buildViolation($constraint->message)
        ->atPath('payer.0')
        ->addViolation();
    }
  }

}
