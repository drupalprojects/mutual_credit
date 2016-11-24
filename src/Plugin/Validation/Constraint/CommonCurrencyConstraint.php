<?php

namespace Drupal\mcapi\Plugin\Validation\Constraint;

use Drupal\mcapi\Exchange;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Check that the payer and payee have access to a common currency.
 *
 * @Constraint(
 *   id = "CommonCurrency",
 *   label = @Translation("Checks that the payer and payee can access all currencies in the transaction"),
 * )
 * @deprecated no need to check for this if all transactions are within one exchange.
 */
class CommonCurrencyConstraint extends CompositeConstraintBase {

  /**
   * The execution context.
   *
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * The error message.
   *
   * @var string.
   */
  public $message = 'The payer and payee have no currencies in common';

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['payer', 'payee', 'worth'];
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    // don't have a separate class for the validator, use this one.
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($transaction, Constraint $constraint) {
    // Check the payer and payee aren't the same.
    foreach ($transaction->flatten() as $trans) {
      // I don't know why but $trans->payer->entity computed property isn't
      // working here this entity level validation runs even if the element
      // level validation failed. To prevent mess, only do this if we have ids
      // for both wallets.
      if ($wid1 = $trans->payer->target_id and $wid2 = $trans->payee->target_id) {
        if ($absent = $this->uncommonCurrencies($wid1, $wid2, $trans->worth)) {
          $this->context
            ->buildViolation($constraint->message)
            // @todo its impossible to identify which currency value once the $list has been built and filtered
            ->atPath('worth')
            ->addViolation();
        }
      }
    }
  }

  /**
   * Show which currencies in a transaction are NOT common to both wallets.
   *
   * @param int $wid1
   *   The first wallet ID.
   * @param int $wid2
   *   The second wallet ID.
   * @param FieldItemListInterface $worth
   *   The transaction worth fieldItemList.
   *
   * @return Currency[]
   *   The currencies not common to both wallets.
   *
   * @todo. This probably isn't needed if we aren't supporting internal intertrading.
   */
  private function uncommonCurrencies($wid1, $wid2, FieldItemListInterface $worth) {
    $curr_keys = $worth->currencies();
    $wallet1_currencies = Exchange::currenciesAvailable(Wallet::load($wid1));
    $wallet2_currencies = Exchange::currenciesAvailable(Wallet::load($wid2));
    // All of the $curr_keys must be in both arrays.
    return array_merge(
      array_diff($curr_keys, array_keys($wallet1_currencies)),
      array_diff($curr_keys, array_keys($wallet2_currencies))
    );
  }

}
