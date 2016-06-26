<?php

namespace Drupal\mcapi_limits\Plugin\Condition;

use Drupal\mcapi\Entity\Transaction;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Transaction Transgresses balance limits' condition.
 *
 * @Condition(
 *   id = "mcapi_transaction_transgresses",
 *   label = @Translation("Transaction transgresses balance limits"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the transaction being inserted")
 *     )
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 */
class TransactionTransgresses extends RulesConditionBase {

  /**
   * Checks if a given entity has a given field.
   *
   * @param \Drupal\mcapi\Entity\Transaction $transaction
   *   The entity to check for the provided field.
   *
   * @return bool
   *   TRUE if the provided entity has the provided field.
   */
  protected function doEvaluate(Transaction $transaction) {
    return isset($transaction->mailLimitsWarning);
  }

}
