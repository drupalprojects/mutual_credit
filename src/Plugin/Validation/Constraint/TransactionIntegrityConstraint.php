<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\TransactionIntegrityConstraint.
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use \Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Supports validating payer & payee of parent transactions
 *
 * @Plugin(
 *   id = "Integrity",
 *   label = @Translation("Checks that the payer and payee wallets are different"),
 *   type = "entity:mcapi_transaction"
 * )
 */
class TransactionIntegrityConstraint extends CompositeConstraintBase {

  public $sameWalletMessage = 'The payer and payee wallets must be different';

  /**
   * {@inheritdoc}
   * how is this used?
   */
  public function coversFields() {
    return ['payer', 'payee', 'parent'];
  }

}
