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
use Drupal\Core\Template\Attribute;

class TransactionForm extends ContentEntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {

    $form = parent::form($form, $form_state);
    $transaction = $this->entity;

    $exchanges = referenced_exchanges();
    if (count($exchanges) < 1) {
      drupal_set_message(t('You are not a member of any exchanges, so you cannot trade with anyone'));
      $form['#disabled'] = TRUE;
    }

    $currencies = exchange_currencies($exchanges);
    $exchanges = array_pad($exchanges, 2, 0);
    //the actual exchange that the transaction takes place in
    //will be determined automatically, once we know who is involved and what currencies.
    //in most use cases only one will be possible or likely
    //until then we offer a choice of users and currencies
    //from all the exchanges the current user is a member of

    unset($form['langcode']); //No language so we remove it.

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $transaction->description->value,
      '#weight' => 3,
      //the empty class is required to prevent an overload error in alpha7
      '#attributes' => new Attribute(array('style' => "width:100%", 'class' => array()))
    );
    $form['worths'] = array(
      '#type' => 'worths',
      '#title' => t('Worth'),
      '#required' => TRUE,
      '#default_value' => $transaction->worths[0],
      //by default, which this is, all the currencies of the currency exchanges should be included
      '#currencies' => $currencies,
      '#weight' => 5,
    );

    //lists all the wallets in the exchange
    $form['payer'] = array(
      '#title' => t('Wallet to be debited'),
      '#type' => 'select_wallet',
      '#local' => TRUE,
      '#default_value' => $transaction->get('payer')->value,
      '#weight' => 9,
    );
    $form['payee'] = array(
      '#title' => t('Wallet to be credited'),
      '#type' => 'select_wallet',
      '#local' => TRUE,
      '#default_value' => $transaction->get('payee')->value,
      '#weight' => 9,
    );
    //TODO how is this field validated? Is it just a positive integer?
    $form['created'] = array(
      '#title' => t('Recorded on'),
      '#type' => 'date',
      '#default_value' => $transaction->get('created')->value,
      '#weight' => 15
    );
    $form['type'] = array(
      '#title' => t('Transaction type'),
      '#options' => mcapi_get_types(TRUE),
      '#type' => 'mcapi_types',
      '#default_value' => $transaction->get('type')->value,
      '#required' => TRUE,
      '#weight' => 18,
    );
    return $form;
  }

  /**
   * form validation callback
   * I can't imagine why, but this is called twice when the form is submitted
   * since validation is an intensive process, perhaps we need a #mcapi_validated flag?
   *
   * this is unusual because normally build a temp object
   */
  public function validate(array $form, array &$form_state) {
    form_state_values_clean($form_state);//without this, buildentity fails, but again, not so in nodeFormController

    //on the admin form it is possible to change the transaction type
    //so here we're going to ensure the state is correct, even if it was set in preCreate
    //actually this should probably happen in Entity prevalidate, not in the form
    $types = mcapi_get_types();
    $type = $form_state['values']['type'];
    $form_state['values']['state'] = $types[$type]->start_state;

    $transaction = $this->buildEntity($form, $form_state);
    $transaction->set('created', REQUEST_TIME);
    $transaction->set('creator', \Drupal::currentUser()->id());

    if (array_key_exists('mcapi_validated', $form_state))return;
    else $form_state['mcapi_validated'] = TRUE;

    //this might throw errors
    //messages may be errors from child transactions
    $messages = $transaction->validate();

    //this is how we show all the messages.
    //setErrorByName can only be set once per form
    foreach ($transaction->exceptions as $e) {
      $exceptions[] = $e->getMessage();
    }
    if (@$exceptions) {
      //TODO why doesn't the error message show?
      \Drupal::formBuilder()->setErrorByName($e->getField(), $form_state, implode(' ', $exceptions));
    }

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
    }
    $this->entity = $transaction;
  }

  public function submit(array $form, array &$form_state) {
    $tempStore = \Drupal::service('user.tempstore');
    $tempStore->get('TransactionForm')->set('entity', $this->entity);
    //Drupal\mcapi\ParamConverter\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Operation\Create

    //now we divert to the operation confirm form
    $form_state['redirect'] = 'transaction/0/create';
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

