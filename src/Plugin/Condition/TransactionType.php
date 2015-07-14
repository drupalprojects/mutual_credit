<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\TransactionType.
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Transaction is of type' condition.
 * @todo see https://www.drupal.org/node/2284687
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
 */
class TransactionType extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    //hopefully the type names are translated
    $states = array_filter($this->configuration['states']);
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
      '#multiple' => TRUE
    ];
    return parent::buildConfigurationForm($form, $form_state);
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
