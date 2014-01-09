<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityFormController;
use Drupal\mcapi\TransactionViewBuilder;
use Drupal\mcapi\McapiTransactionException;
use Drupal\action\Plugin\Action;

class TransactionForm extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {

    $form = parent::form($form, $form_state);
    $form = $this->form_step_1($form, $form_state);
    $form['#validate'][] = array($this, 'step_1_validate');
    $form['#submit'][] = array($this, 'step_1_submit');
    return $form;

    if (empty($form_state['mcapi_submitted'])) {
    }
    else {

      $form = $this->form_step_2($form, $form_state);
      //$form['#validate'][] = array($this, 'step_2_validate');
      $form['#submit'][] = array($this, 'step_2_submit');
    }
    return $form;
  }

  private function form_step_1($form, &$form_state) {
    $transaction = $this->entity;

    unset($form['langcode']); // No language so we remove it.

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
    );
    //the default payer and payee widgets allow anyone with 'transact' permission
    //the transaction entity will check that the users have permission to use the currencies
    //if we know the currency of the transaction, we can form_alter these
    $form['payer'] = array(
      '#title' => t('Account to be debited'),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('transact'),
      '#default_value' => $transaction->payer->value,
    );
    $form['payee'] = array(
      '#title' => t('Account to be credited'),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('transact'),
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
      '#type' => 'user_chooser_few',
    	'#callback' => 'user_chooser_segment_perms',
    	'#args' => array('transact'),
      '#default_value' => $transaction->creator->value,
      '#args' => array('transact'),
      '#required' => FALSE,//because user_chooser assumes TRUE
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

  private function form_step_2($form, &$form_state) {
    $transactions = array();//need the transactions as if loaded, with children and all
    $form['preview'] = array();//TODO
    $form['#markup'] = 'Are you sure?';

    return $form;
  }

  /**
   * form validation callback
   * I can't imagine why, but this is called twice when the form is submitted
   * since validation is an intensive process, perhaps we need a #mcapi_validated flag?
   *
   * this is unusual because normally build a temp object
   */
  public function step_1_validate(array $form, array &$form_state) {
//    parent::validate($form, $form_state);//this makes an infinite loop here but not in nodeFormController
    form_state_values_clean($form_state);//without this, buildentity fails, but again, not so in nodeFormController

    //on the admin form it is possible to change the transaction type
    //so here we're going to ensure the state is correct, even through it was set in preCreate
    //actually this should probably happen in Entity prevalidate, not in the form
    $types = mcapi_get_types();
    $type = $form_state['values']['type'];
    $form_state['values']['state'] = $types[$type]->start_state;

    $transaction = $this->buildEntity($form, $form_state);


    if (array_key_exists('mcapi_validated', $form_state))return;
    else $form_state['mcapi_validated'] = TRUE;

    //this might throw errors
    $messages = $transaction->validate();
    foreach ($transaction->exceptions as $e) {
      \Drupal::formBuilder()->setErrorByName($e->getField(), $form_state, $e->getMessage());
    }
    //TODO sort out entity reference field iteration
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

  public function step_1_submit(array $form, array &$form_state) {
    $tempStore = \Drupal::service('user.tempstore')
    ->get('TransactionForm')
    ->set('entity', $this->entity);

    //now we divert to the operation confirm form
    $form_state['redirect'] = 'transaction/0/confirm';
    //the transaction is confirmed using the operation plugin, Confirm, see
    //Drupal\mcapi\ParamConverter\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Operation\Confirm
  }

  public function step_2_validate(array $form, array &$form_state) {
    //the only reason we might need this function is if there should be
    //an extra input field for transaction ratings
  }

  /**
   * form submit callback
   */
  public function step_2_submit($form, &$form_state) {

  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $transaction = $this->entity;
    try {
      $db_t = db_transaction();
      //was already validated
      $status = $transaction->save($form, $form_state);
    }
    catch (Exception $e) {
      \Drupal::formBuilder()->setErrorByName(
        'actions',
        t("Failed to save transaction: @message", array('@message' => $e->getMessage))
      );
      $db_t->rollback();
    }

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Transaction %label has been updated.', array('%label' => $transaction->label())));
    }
    else {
      drupal_set_message(t('Transaction %label has been added.', array('%label' => $transaction->label())));
    }
    $link = $transaction->uri();
    $form_state['redirect'] = $link['path'];
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   * Currently there is no transaction delete form
   */
  public function delete(array $form, array &$form_state) {
    //$form_state['redirect'] = 'admin/accounting/currencies/' . $this->entity->id() . '/delete';
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, array &$form_state) {
    if (\Drupal::formBuilder()->getErrors($form_state)) return;
    $actions = array(
      // @todo Rename the action key from submit to save.
      'submit' => array(
        '#value' => $this->t('Save'),
        '#validate' => array(
          array($this, 'validate'),
        ),
        '#submit' => array(
          array($this, 'submit'),
        ),
      ),
    );
    if (empty($form_state['mcapi_submitted'])) {
      $actions['submit']['#validate'][] = array($this, 'step_1_validate');
      $actions['submit']['#submit'][] = array($this, 'step_1_submit');
    }
    else {
      $actions['submit']['#validate'][] = array($this, 'step_2_validate');
      $actions['submit']['#submit'][] = array($this, 'step_2_submit');
      $actions['submit']['#submit'][] = array($this, 'save');
      $actions['back'] = array(
        '#value' => t('Back'),
        '#submit' => array(array($this, 'back'))
      );
    }
    return $actions;
  }

  public function back(&$form, &$form_state) {
    $form_state['rebuild'] = TRUE;
    $form_state['mcapi_submitted'] = FALSE; //this means we move to step 2 regardless
  }

}

