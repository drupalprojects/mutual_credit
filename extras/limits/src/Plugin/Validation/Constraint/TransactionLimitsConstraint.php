<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\Plugin\Validation\Constraint\TransactionLimitsConstraint.
 *
 * @todo find out if there is any advantage to just using the parent and remove if not
 *
 */

namespace Drupal\mcapi_limits\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating payer & payee of parent transactions
 *
 * @Plugin(
 *   id = "Limits",
 *   label = @Translation("Checks that neither wallet goes beyond its determined credit limits"),
 *   type = "entity:mcapi_transaction"
 * )
 */
class TransactionLimitsConstraint extends Constraint {

  public $overLimit = "The transaction would take wallet '!wallet' !excess above the maximum limit of !limit.";

  public $underLimit = "The transaction would take wallet '!wallet' !excess below the minimum limit of !limit.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['payer', 'payee', 'worth', 'state'];
  }


}
