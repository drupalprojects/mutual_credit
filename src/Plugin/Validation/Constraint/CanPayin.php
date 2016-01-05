<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\CanPayin.
 * Combined Constraint / Constraintvalidator
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the current user is authorised to pay in to the payer wallet
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
  public $message = 'You are not allowed to pay into this wallet';

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $currentUser = \Drupal::currentUser();
    if ($currentUser->isAnonymous()) {
      //intertrading transactions may involve anonymous in which case we trust external validation
      return;
    }
    $result = \Drupal::entityTypeManager()
      ->getAccessControlHandler('mcapi_wallet')
      ->checkAccess(\Drupal\mcapi\Entity\Wallet::load($items->target_id), 'payin', $currentUser);
    
    if ($result->isForbidden()) {
      $this->context->addViolation('message', []);
    }
  }
}
