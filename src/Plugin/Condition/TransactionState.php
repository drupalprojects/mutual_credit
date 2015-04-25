<?php
/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\TransactionState.
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Transaction is in state' condition.
 *
 * @Condition(
 *   id = "mcapi_transaction_state",
 *   label = @Translation("Transaction is in state"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "transaction" = @ContextDefinition("entity:mcapi_transaction",
 *       label = @Translation("Transaction"),
 *       description = @Translation("Specifies the transaction to test.")
 *     ),
 *     "state" = @ContextDefinition("entity:mcapi_state",
 *       label = @Translation("States"),
 *       description = @Translation("All the possible transaction states"),
 *       multiple = TRUE
 *     )
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 * @todo: Add group information from Drupal 7.
 * @todo: Makek this disappear from the block-page, which seems not to check for contexts
 */
class TransactionState extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Transaction is in state');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $transaction = $this->getContextValue('transaction');
    $types = $this->getContextValue('types');
    return in_array($transaction->state->target_id, $types);
  }

}
