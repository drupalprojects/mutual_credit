<?php

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Transaction is of type' condition.
 *
 * @todo see https://www.drupal.org/node/2284687
 *
 * @Condition(
 *   id = "mcapi_transaction_type",
 *   label = @Translation("Transaction is of type"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "transaction" = @ContextDefinition("entity:mcapi_transaction",
 *       label = @Translation("Transaction"),
 *     ),
 *     "types" = @ContextDefinition("entity:mcapi_type",
 *       label = @Translation("Types"),
 *       description = @Translation("Check for the allowed transaction types"),
 *       multiple = TRUE
 *     )
 *   }
 * )
 */
class TransactionType extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Hopefully the type names are translated.
    return $this->t(
      'Transaction type is @types',
      ['@types' => implode(', ', $this->configuration['types'])]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'types' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['types'] = [
      '#type' => 'mcapi_types',
      '#default_value' => $this->configuration['types'],
      '#multiple' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function doEvaluate($transaction, $types) {
    return in_array($transaction->type->target_id, $types);
  }

}
