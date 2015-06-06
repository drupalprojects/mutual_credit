<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\CanActOn.
 * Combined Constraint / Constraintvalidator
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Drupal\mcapi\Exchange;

/**
 * Checks if the current user is authorised to pay out of the payer wallet
 *
 * @Plugin(
 *   id = "CanActOn",
 *   label = @Translation("Allowed to pay out of the wallet")
 * )
 */
class CanActOn extends Constraint implements ConstraintValidatorInterface {

  /**
   * Violation message.
   *
   * @var string
   */
  public $payout = 'You are not allowed to pay out of this wallet';

  /**
   * Violation message.
   *
   * @var string
   */
  public $payin = 'You are not allowed to pay into this wallet';

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  protected $action;


    public function __construct($options = null) {
      if (!is_array($options)) {
        return;
      }
      if (!in_array($options['action'], array_keys(Exchange::WalletOps()))) {
        throw new ConstraintDefinitionException(
          t(
            "Invalid action option '@option' for constraint @name",
            ['@name' => 'CanActOn', '@option' => $options['action']]
          )
        );
      }
      $this->action = $options['action'];
    }

  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    //don't have a separate class for the validator, use this one.
    return get_class($this);
  }

  public function getRequiredOptions() {
    return ['action'];
  }


  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $wallets = $items->referencedEntities();
    $access = \Drupal::entityManager()
      ->getAccessControlHandler('mcapi_wallet')
      ->checkAccess(reset($wallets), $this->action, NULL, \Drupal::currentUser());
    //might want to inject the currentuser?
    if (!$access) {
      $this->context->addViolation($this->{$$action}, []);
    }
  }

}