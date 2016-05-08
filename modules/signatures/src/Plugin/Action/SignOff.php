<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Plugin\Actions\Signoff
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Action;

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
class Signoff extends \Drupal\mcapi\Plugin\TransactionActionBase {

  /*
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    $elements['states'] = [
      '#type' => 'value',
      '#value' => ['pending' => 'pending']
    ];
    return $elements;
  }

  /*
   * {@inheritdoc}
  */
  public function execute($transaction = NULL) {
    foreach ($transaction->signatures as $uid => $signed) {
      if ($signed) {
        continue;
      }
      \Drupal::service('mcapi.signatures')
      ->setTransaction($transaction)
      ->sign($uid);
    }
    parent::execute($transaction);
  }

}
