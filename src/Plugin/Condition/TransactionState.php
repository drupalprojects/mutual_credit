<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\TransactionState.
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
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
 */
class TransactionState extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    //hopefully the state names are translated
    $states = array_filter($this->configuration['states']);
    return $this->t(
      'Transaction state is @states',
      ['@states' => implode(',', $states)]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['states'] = [
      '#type' => 'mcapi_states',
      '#default_value' => $this->configuration['states'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
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
