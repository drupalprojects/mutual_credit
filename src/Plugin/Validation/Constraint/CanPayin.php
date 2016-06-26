<?php

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the current user is authorised to pay in to the payer wallet.
 *
 * @Constraint(
 *   id = "CanPayin",
 *   label = @Translation("Allowed to pay in to the wallet")
 * )
 */
class CanPayin extends CanPay {

  /**
   * Violation message.
   *
   * @var string
   */
  public $message = 'Not allowed to pay into wallet @wid';

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if ($items->restricted) {
      // $items is the payee wallet listfield.
      $eligible = \Drupal::entityTypeManager()
        ->getStorage('mcapi_wallet')
        ->whichWalletsQuery('payin', \Drupal::currentUser()->id());

      if (!in_array($items->target_id, $eligible)) {
        $this->addViolation($this->message, ['@wid' => $items->target_id]);
      }
    }
  }

}
