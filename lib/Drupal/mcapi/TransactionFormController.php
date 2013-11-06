<?php

use Drupal\Core\Template\Attribute;

/**
 * @file
 * Definition of Drupal\mcapi\CurrencyFormController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityFormController;

class TransactionFormController extends EntityFormController {
  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
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
      '#default_value' => $transaction->worths,
    );
    //the default payer and payee widgets will autocomplete any user on the system, and permissions will be checked later
    $form['payer'] = array(
      '#title' => t('Account to be debited'),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('transact'),
      '#default_value' => $transaction->payer->value,
      '#weight' => 6,
    );
    $form['payee'] = array(
      '#title' => t('Account to be credited'),
      '#type' => 'user_chooser_few',
      '#callback' => 'user_chooser_segment_perms',
      '#args' => array('transact'),
      '#default_value' => $transaction->payee->value,
      '#weight' => 9,
    );
    $form['type'] = array(
      '#title' => t('Transaction type'),
      '#options' => drupal_map_assoc(module_invoke_all('mcapi_info_types')),
      '#type' => 'value',
      '#default_value' => $transaction->type->value,
      '#element_validate' => array('mcapi_validate_ttype'),
      '#required' => TRUE,
      '#weight' => 15
    );
    $form['state'] = array(
      '#title' => t('State'),
      '#description' => mcapi_get_states('#description'),
      '#type' => 'value',
      '#options' => mcapi_get_states('#options'),
      '#default_value' => $transaction->state->value,
      '#element_validate' => array('mcapi_validate_state'),
      '#weight' => 18
    );
    $form['creator'] = array(
      '#title' => t('Recorded by'),
      '#type' => 'value',
      '#default_value' => $transaction->creator->value,
      '#args' => array('transact'),
      '#required' => TRUE,
      '#weight' => 20,
    );

    //FIXME: Fix up submit button to say 'Record' instead of save
    /* $form['buttons'] = array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => t('Record'),
        //this prevents double click, but doesn't prevent going back and resubmitting the form
        '#attributes' => array('onclick' => "this.disabled=true,this.form.submit();"),
      ),
      '#weight' => 25
    ); */

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $transaction = $this->entity;
    $status = $transaction->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Transaction %label has been updated.', array('%label' => $transaction->label())));
    }
    else {
      drupal_set_message(t('Transaction %label has been added.', array('%label' => $transaction->label())));
    }

    $form_state['redirect'] = '';
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    //$form_state['redirect'] = 'admin/accounting/currencies/' . $this->entity->id() . '/delete';
  }

}