<?php

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Transaction is in state' condition.
 *
 * @todo see https://www.drupal.org/node/2284687
 *
 * @Condition(
 *   id = "mcapi_transaction_state",
 *   label = @Translation("Transaction is in state"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "transaction" = @ContextDefinition("entity:mcapi_transaction",
 *       label = @Translation("Transaction"),
 *     ),
 *     "states" = @ContextDefinition("entity:mcapi_state",
 *       label = @Translation("States"),
 *       description = @Translation("Check for the allowed transaction  states"),
 *       multiple = TRUE
 *     )
 *   }
 * )
 */
class TransactionState extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Hopefully the state names are translated.
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
      '#multiple' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function doEvaluate($args) {
    $transaction = $this->getContextValue('transaction');
    $types = $this->getContextValue('types');
    return in_array($transaction->state->target_id, $types);
  }

}
