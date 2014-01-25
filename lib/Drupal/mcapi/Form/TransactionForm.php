<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionForm.
 * Edit all the fields on a transaction
 *
 * We have to start by working out the what exchange this transaction
 * is happening in. This helps us to work out what currencies to show
 * and what users to make available in the user selection widgets
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\mcapi\TransactionViewBuilder;
use Drupal\mcapi\McapiTransactionException;
use Drupal\action\Plugin\Action;

class TransactionForm extends ContentEntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {

    $form = parent::form($form, $form_state);
    $transaction = $this->entity;

    $exchanges = referenced_exchanges(\Drupal::currentUser(), 'field_exchanges');
    $currencies = exchange_currencies($exchanges);
    //the actual exchange that the transaction takes place in
    //will be determined automatically, once we know who is involved and what currencies.
    //in most use cases only one will be possible or likely
    echo 'This transaction will be in exchange '.implode(' or ', array_keys($exchanges)).'.';
    echo '<br />And in currencies '.implode(' or ', array_keys($currencies)).'.';

    unset($form['langcode']); //No language so we remove it.

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $transaction->description->value,
    );
    $form['worths'] = array(
      '#type' => 'worths',
      '#title' => t('Worth'),
      '#required' => TRUE,
      '#default_value' => $transaction->worths[0],
      //by default, which this is, all the currencies of the currency exchanges should be included
      '#currencies' => $currencies
    );

    //@todo GORDON what's the best way to list the wallets of the members of the current exchange
    //including any wallets whose parent is the exchange itself?
    //I think what we need is a wallet_chooser element!
    $form['payer'] = array(
      '#title' => t('Wallet to be debited'),
      '#type' => 'entity_chooser',
      '#plugin' => 'wallet',
      '#args' => array(),
      '#default_value' => $transaction->payer->value,
    );
    $form['payee'] = array(
      '#title' => t('Wallet to be credited'),
      '#type' => 'entity_chooser',
      '#plugin' => 'wallet',
      '#args' => array(),
      '#default_value' => $transaction->payee->value,
    );
    $form['type'] = array(
      '#title' => t('Transaction type'),
      '#options' => mcapi_get_types(TRUE),
      '#type' => 'mcapi_types',
      '#default_value' => $transaction->type->value,
      '#required' => TRUE,
    );
    $form['creator'] = array(
      '#title' => t('Recorded by'),
      '#type' => 'entity_chooser',
    	'#plugin' => 'user',
    	'#args' => array(),
      '#default_value' => $transaction->creator->value,
      '#weight' => 15,
    );
    //TODO how is this field validated? Is it just a positive integer?
    $form['created'] = array(
      '#title' => t('Recorded on'),
      '#type' => 'date',
      '#default_value' => $transaction->created->value,
      '#weight' => 18,
    );
    return $form;
  }
/*
  private function form_step_2($form, &$form_state) {
    $transactions = array();//need the transactions as if loaded, with children and all
    $form['preview'] = array();//TODO
    $form['#markup'] = 'Are you sure?';

    return $form;
  }
*/
  /**
   * form validation callback
   * I can't imagine why, but this is called twice when the form is submitted
   * since validation is an intensive process, perhaps we need a #mcapi_validated flag?
   *
   * this is unusual because normally build a temp object
   */
  public function validate(array $form, array &$form_state) {
//    parent::validate($form, $form_state);//this makes an infinite loop here but not in nodeFormController
    form_state_values_clean($form_state);//without this, buildentity fails, but again, not so in nodeFormController

    //on the admin form it is possible to change the transaction type
    //so here we're going to ensure the state is correct, even through it was set in preCreate
    //actually this should probably happen in Entity prevalidate, not in the form
    $types = mcapi_get_types();
    $type = $form_state['values']['type'];
    $form_state['values']['state'] = $types[$type]->start_state;

    $transaction = $this->buildEntity($form, $form_state);
    $transaction->set('created', REQUEST_TIME);


    if (array_key_exists('mcapi_validated', $form_state))return;
    else $form_state['mcapi_validated'] = TRUE;

    //this might throw errors
    $messages = $transaction->validate();
    foreach ($transaction->exceptions as $e) {
      \Drupal::formBuilder()->setErrorByName($e->getField(), $form_state, $e->getMessage());
    }
    //TODO sort out entity reference field iteration
    //except that children is not an entity reference field, is it?
    /*
    $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
    foreach ($transaction->children as $child) {
      foreach ($child->exceptions as $e) {
        if (!$child_errors['allow']) {
          \Drupal::formBuilder()->setErrorByName($e->getField(), $form_state, $e->getMessage());
        }
        elseif ($child_errors['show_messages']) {
          drupal_set_message($e->getMessage, 'warning');
        }
      }
    }*/
    //form_state['mcapi_submitted'] = TRUE;//this means we move to step 2
    $this->entity = $transaction;
  }

  public function submit(array $form, array &$form_state) {
    $tempStore = \Drupal::service('user.tempstore')
    ->get('TransactionForm')
    ->set('entity', $this->entity);

    //now we divert to the operation confirm form
    $form_state['redirect'] = 'transaction/0/create';
    //the transaction is confirmed using the operation plugin, Confirm, see
    //Drupal\mcapi\ParamConverter\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Operation\Create
  }


  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, array &$form_state) {
    if (\Drupal::formBuilder()->getErrors($form_state)) return;
    $actions = array(
      'save' => array(
        '#value' => $this->t('Save'),
        '#validate' => array(
          array($this, 'validate'),
        ),
        '#submit' => array(
          array($this, 'submit'),
        ),
      ),
    );
    return $actions;
  }

}

