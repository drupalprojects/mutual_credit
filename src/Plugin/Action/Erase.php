<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Erase
 * 
 * @todo this plugin could have an option to erase either by changing the 
 * transaction state OR by adding a reversal transaction to the cluster. It must
 * be either/or because the two mechanisms cannot happen to different currencies 
 * in the same transaction
 *
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Changes the transaction state to 'erased'.
 *
 * @Action(
 *   id = "mcapi_transaction.erase_action",
 *   label = @Translation("Erase a transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "mcapi.transaction.operation"
 * )
 */
class Erase extends \Drupal\mcapi\Plugin\TransactionActionBase {
    
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    $elements['states'][TRANSACTION_STATE_ERASED] = [
      '#disabled' => TRUE,//setting #default value seems to have no effect
    ];
    return $elements;
  }
  
  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    //keep a separate record of the previous state of erased transactions, so they can be unerased
    $key_value_store = \Drupal::service('keyvalue.database')
      ->get('mcapi_erased')
      ->set($object->serial->value, $object->state->target_id);

    $saved = $object
      ->set('state', TRANSACTION_STATE_ERASED);//will be saved later

  }
  
  
  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {die('isConfigurable');
    //prevents this action being added multiple times on admin/config/system/actions
    return FALSE;
  }

  
  public function executeMultiple(array $objects) {
      parent::executeMultiple();
      die('Delete::executeMultiple()');
  }

}
