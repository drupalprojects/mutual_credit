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
    foreach (['payer', 'payee'] as $trader) {
      $$trader = $transaction->{$trader}->target_id;
      if (!$$trader) {
        $this->context
          ->buildViolation($constraint->nullMessage)
          ->atPath($trader.'.0')
          ->addViolation();
      }
    }
    // Check the payer and payee aren't the same.
    if ($payer === $payee) {
      $this->context
        ->buildViolation($constraint->sameMessage)
        ->atPath('payer.0')
        ->addViolation();
    }
  }

}
