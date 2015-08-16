<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\TransactionIntegrityConstraintValidator.
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

class TransactionIntegrityConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    //check the payer and payee aren't the same
    foreach ($transaction->flatten() as $transaction) {
      if ($transaction->payer->target_id == $transaction->payee->target_id) {
        $this->context
          ->buildViolation($constraint->sameWalletMessage)
          ->atPath('payer')
          ->addViolation();
      }
    }
  }
}