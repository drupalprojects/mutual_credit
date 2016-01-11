<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Validation\Constraint\CommonCurrencyConstraint.
 */

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use \Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;
use Drupal\mcapi\Entity\Wallet;

/**
 * Supports validating payer & payee of parent transactions
 *   type = "entity"...
 *
 * @Constraint(
 *   id = "CommonCurrency",
 *   label = @Translation("Checks that the payer and payee can access all currencies in the transaction"),
 * )
 */
class CommonCurrencyConstraint extends CompositeConstraintBase {
  
  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;
  
  public $message = 'The payer and payee have no currencies in common';
  
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

    
  /**
   * {@inheritdoc}
   * how is this used?
   */
  public function coversFields() {
    return ['payer', 'payee', 'worth'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    //don't have a separate class for the validator, use this one.
    return get_class($this);
  }
  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    $absent = [];
    //check the payer and payee aren't the same
    foreach ($transaction->flatten() as $trans) {
      //I don't know why but $trans->payer->entity computed property isn't working here
      if ($absent = $this->uncommonCurrencies($trans->payer->target_id, $trans->payee->target_id, $trans->worth)) {
        $this->context
          ->buildViolation($constraint->message)
          ->atPath('worth')//@todo its impossible to identify which currency value once the $list has been built and filtered
          ->addViolation();
      }

    }
  }
  /**
   * utility
   * show which currencies in a transaction are NOT common to both wallets
   * 
   * @param integer $wid1
   * @param integer $wid2
   * @param FieldItemList $worth
   * @return array
   */
  private function uncommonCurrencies($wid1, $wid2, $worth) {
    $curr_keys = $worth->currencies();
    //all of the $currencies must be in both $payer_currencies and $payee_currncies
    $wallet1_currencies = Wallet::load($wid1)->currenciesAvailable();
    $wallet2_currencies = Wallet::load($wid2)->currenciesAvailable();
    return array_merge(
      array_diff($curr_keys, array_keys($wallet1_currencies)),
      array_diff($curr_keys, array_keys($wallet2_currencies))
    );
  }

}
