<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Unerase
 *
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Changes the transaction state to what it was before it was erased.
 *
 * @Action(
 *   id = "mcapi_transaction.unerase_action",
 *   label = @Translation("Unerase a transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "mcapi.transaction.operation"
 * )
 */
class Unerase extends \Drupal\mcapi\Plugin\TransactionActionBase {

  /**
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    $elements['states'] = [
      '#type' => 'value',
      '#value' => ['erased' => 'erased']
    ];
    return $elements;
  }
  
  /**
   * {@inheritdoc}
  */
  public function execute($object = NULL) {
    $store = \Drupal::service('keyvalue.database')->get('mcapi_erased');
    $object->set('state', $store->get($object->serial->value, 'done'));
    $store->delete($this->transaction->serial->value);
  }

}
