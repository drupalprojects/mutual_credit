<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Operations\Edit
 *
 */

namespace Drupal\mcapi\Plugin\Operation;

use Drupal\mcapi\OperationBase;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;

/**
 * Undo operation
 *
 * @Operation(
 *   id = "edit",
 *   label = @Translation("Edit"),
 *   description = @Translation("Edit given fields"),
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Editing..."
 *   }
 * )
 */
class Undo extends OperationBase {

  /*
   * {@inheritdoc}
  */
  public function access_form(CurrencyInterface $currency) {
    //return the access functions for each transaction state
    $element = parent::access_form($currency);
    foreach (mcapi_get_states() as $state) {
      $elements[$constantVal] = $element;
      $elements[$constantVal]['#title'] = $state->label;
      $elements[$constantVal]['#description'] = $state->description;
      $elements[$constantVal]['#default_value'] = $currency->access_undo[$state->value];
    }
  }

  /*
   *  access callback for transaction operation 'view'
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->state->value == TRANSACTION_STATE_UNDONE) RETURN FALSE;
    $access_plugins = transaction_access_plugins();
    //see the comments in OperationBase
    foreach ($transaction->worths[0] as $worth) {
      foreach (@$worth->currency->access_edit[$transaction->state->value] as $plugin) {
        if ($access_plugins[$plugin]->checkAccess($transaction)) continue 2;
      }
      return FALSE;
    }
    return TRUE;
  }

  /*
   * {inheritdoc}
   */
  public function form() {
    foreach ($config->get('fields') as $fieldname) {
      //TODO retrieve the field instances from the transaction entity
      //allow the user to fill them in.
    }
  }

  /*
   * {inheritdoc}
   */
  public function settingsForm(array &$form, ConfigFactory $config) {
    parent::settingsForm($form, $config);
    module_load_include('inc', 'mcapi');
    $form['fields'] = array(
  	  '#title' => t('Editable fields'),
  	  '#description' => t('select the fields which can be edited'),
  	  '#type' => 'checkboxes',
      '#options' => mcapi_transaction_list_tokens(),
  	  '#default_values' => $config->get('fields')
    );
  }

  /*
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $values) {
    $transaction->delete();
    $message = t('The transaction is undone.') .' ';
    return array('#markup' => $message);
  }

}
