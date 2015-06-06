<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\TransactionIntegrityContraintValidator.
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
    if ($transaction->payer->target_id == $child_transaction->payee->target_id) {
      if ($child_transaction->parent->value == 0) {
        $this->context
          ->buildViolation($constraint->sameWalletMessage)
          ->atPath('payer')
          ->addViolation();
      }
      //with children it is less serious
      foreach ($transaction->children as $child) {
        if ($child->payer->target_id == $child->payee->target_id) {
          \Drupal::logger('mcapi')->notice('Child transaction has same payer and payee: '.print_r($transaction->toArray(), 1));
        }
      }
    }
    //testing;
    \Drupal::logger('mcapi')
      ->notice('Child transaction has same payer and payee: '.print_r($transaction->toArray(), 1));

  }

}