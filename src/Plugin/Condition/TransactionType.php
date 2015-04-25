<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\TransactionType.
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Transaction is of type' condition.
 *
 * @Condition(
 *   id = "mcapi_transaction_type",
 *   label = @Translation("Transaction is of type"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "transaction" = @ContextDefinition("entity:mcapi_transaction",
 *       label = @Translation("Transaction"),
 *       description = @Translation("Specifies the transaction to test.")
 *     ),
 *     "type" = @ContextDefinition("entity:mcapi_type",
 *       label = @Translation("Types"),
 *       description = @Translation("All the possible transaction types"),
 *       multiple = TRUE
 *     )
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 * @todo: Add group information from Drupal 7.
 */
class TransactionType extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Transaction is of type');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $transaction = $this->getContextValue('transaction');
    $types = $this->getContextValue('types');
    return in_array($transaction->type->target_id, $types);
  }

}
