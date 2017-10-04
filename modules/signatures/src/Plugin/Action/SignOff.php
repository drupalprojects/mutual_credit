<?php

namespace Drupal\mcapi_signatures\Plugin\Action;

use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Changes the transaction state from pending to 'done'.
 *
 * @Action(
 *   id = "mcapi_transaction.signoff_action",
 *   label = @Translation("Sign off a transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "mcapi.transaction.operation"
 * )
 */
class Signoff extends TransactionActionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    $elements['states'] = [
      '#type' => 'value',
      '#value' => ['pending' => 'pending'],
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($transaction = NULL) {
    \Drupal::service('mcapi.signatures')
      ->setTransaction($transaction)
      ->signOff();
    parent::execute($transaction);
  }

}
