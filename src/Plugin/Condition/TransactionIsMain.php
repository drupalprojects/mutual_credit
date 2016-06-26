<?php

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Transaction is main' condition.
 *
 * @Condition(
 *   id = "mcapi_transaction_is_main",
 *   label = @Translation("Transaction is main"),
 *   description = @Translation("Transaction is the first or only one in the cluster (with the same serial number)"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "node" = @ContextDefinition("entity:mcapi_transaction",
 *       label = @Translation("Transaction")
 *     )
 *   }
 * )
 */
class TransactionIsMain extends RulesConditionBase {

  /**
   * Checks if a transaction is the main, or parent in the cluster.
   *
   * @param \Drupal\mcapi\Entity\TransactionInterface $transaction
   *   The transaction to check.
   *
   * @return bool
   *   TRUE if the transaction is the main one in the cluster.
   */
  protected function doEvaluate(TransactionInterface $transaction) {
    return $transaction->parent->value == 0;
  }

}
