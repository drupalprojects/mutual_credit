<?php

namespace Drupal\mcapi_limits\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating payer & payee of parent transactions.
 *
 * @Constraint(
 *   id = "Limits",
 *   label = @Translation("Checks that neither wallet goes beyond its determined credit limits"),
 *   type = "entity:mcapi_transaction"
 * )
 */
class TransactionLimitsConstraint extends Constraint {

  public $overLimitBlock = "The transaction would take wallet '%wallet' %excess above the maximum limit of %limit.";

  public $underLimitBlock = "The transaction would take wallet '%wallet' %excess below the minimum limit of %limit.";

  public $overLimitWarning = "The transaction took wallet '%wallet' %excess above the maximum limit of %limit.";

  public $underLimitWarning = "The transaction took wallet '%wallet' %excess below the minimum limit of %limit.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['payer', 'payee', 'worth', 'state'];
  }

}
