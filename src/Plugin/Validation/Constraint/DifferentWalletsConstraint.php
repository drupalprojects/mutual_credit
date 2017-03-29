<?php

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use \Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Check that the two wallets in the transaction are different.
 *
 * Supports validating payer & payee of parent transactions.
 *
 * @Constraint(
 *   id = "DifferentWallets",
 *   label = @Translation("Checks that the payer and payee wallets are different"),
 * )
 */
class DifferentWalletsConstraint extends CompositeConstraintBase {

  public $nullMessage = 'The wallet value cannot be null';
  public $sameMessage = 'The payer and payee wallets must be different';

  /**
   * {@inheritdoc}
   *
   * How is this used?
   */
  public function coversFields() {
    return ['payer', 'payee'];
  }

}
