<?php

/**
 * @file
 *  Contains Drupal\mcapi_signatures\Plugin\Actions\Sign
 *
 */

namespace Drupal\mcapi_signatures\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Changes the transaction state from pending to 'done'.
 *
 * @Action(
 *   id = "mcapi_transaction.sign_action",
 *   label = @Translation("Add your signature to a pending transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "mcapi.transaction.operation"
 * )
 */
class Sign extends \Drupal\mcapi\Plugin\TransactionActionBase {

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
    \Drupal\mcapi_signatures\Signatures::sign($transaction, \Drupal::currentUser());
    parent::execute($transaction);
  }


}
