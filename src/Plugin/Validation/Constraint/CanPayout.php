<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\CanPayout.
 * Combined Constraint / Constraintvalidator
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the current user is authorised to pay out of the payer wallet
 *
 * @Constraint(
 *   id = "CanPayout",
 *   label = @Translation("Allowed to pay out of the wallet")
 * )
 */
class CanPayout extends CanPay {

  /**
   * Violation message.
   *
   * @var string
   */
  public $message = 'You are not allowed to pay out of this wallet';

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    //$items is the payer wallet listfield
    $eligible = \Drupal::entityTypeManager()
      ->getStorage('mcapi_wallet')
      ->whichWalletsQuery('payout', \Drupal::currentUser()->id());

    if (!in_array($items->target_id, $eligible)) {
      $this->context->addViolation('message', []);
    }
  }
}
