<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Edit
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\CurrencyInterface;

/**
 * Edit transition
 *
 * @Transition(
 *   id = "edit",
 *   label = @Translation("Edit"),
 *   description = @Translation("Edit given fields"),
 *   settings = {
 *     "weight" = "2",
 *     "sure" = "Editing..."
 *   }
 * )
 */
class Edit extends TransitionBase {

  /**
   * {@inheritdoc}
  */
  public function access_form(array $defaults) {

    return $elements;
  }

  /**
   *  access callback for transaction transition 'edit'
  */
  public function opAccess(TransactionInterface $transaction) {
    if ($transaction->state->value == TRANSACTION_STATE_UNDONE) {
      return FALSE;
    }
    $days = @$this->settings['window'];
    if ($transaction->created->value + 86400*$days < REQUEST_TIME) {
      return FALSE;
    }
    $access_plugins = transaction_access_plugins();
    //see the comments in TransitionBase
    foreach ($transaction->worths[0] as $worth) {
      foreach (@$worth->currency->access_edit[$transaction->state->value] as $plugin) {
        if ($access_plugins[$plugin]->checkAccess($transaction)) continue 2;
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {inheritdoc}
   */
  public function form(TransactionInterface $transaction) {
    foreach (array_filter($this->settings['fields']) as $fieldname) {
      //TODO retrieve the field instances from the transaction entity
      //allow the user to fill them in.
    }
  }

  /**
   * {inheritdoc}
   */
  public function settingsForm(array &$form) {
    parent::settingsForm($form);
    module_load_include('inc', 'mcapi');
    $form['fields'] = array(
  	  '#title' => t('Editable fields'),
  	  '#description' => t('select the fields which can be edited'),
  	  '#type' => 'checkboxes',
      '#options' => mcapi_transaction_list_tokens(),
  	  '#default_values' => $this->settings['fields']
    );
    $form['window'] = array(
    	'#title' => t('Editable window'),
      '#description' => t('Number of days after creation that the transaction can be edited'),
      '#type' => 'number',
  	  '#default_values' => $this->settings['window'],
      '#min' => 0
    );
  }
  /**
   * {@inheritdoc}
  */
  public function execute(TransactionInterface $transaction, array $context) {

    $violations = array('Edit has not been programmed yet');
    if ($violations) {
      throw new McapiTransactionException('', $message[0]);
    }
    parent::execute($transaction, $context);
    return array('#markup' => 'This code has not been written!');
  }

}
