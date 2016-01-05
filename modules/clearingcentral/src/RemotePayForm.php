<?php

/**
 * @file
 * Definition of Drupal\mcapi_cc\RemotePayForm.
 * Extend the transaction form
 * It was too much trouble to make this work with the FirstParty form builder
 */

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Form\TransactionForm;
use Drupal\Core\Form\FormStateInterface;

class RemotePayForm extends TransactionForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    print_r(array_keys($form));
    
    
    return $form;
  }
  

  /**
   * we don't call the 
   */
  final public function validatetForm(array &$form, FormStateInterface $form_state) {
    

  }
  
    /**
   * {@inheritdoc}
   * 
   * @note does NOT call parent.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
    //these values are picked up in mcapi_cc_mcapi_transaction_insert
    $this->entity->remote_exchange_id = $form_state->getValue('remote_exchange_id');
    $this->entity->remote_user_id = $form_state->getValue('remote_user_id');
    $this->entity->remote_user_name = $form_state->getValue('remote_user_name');
    
    $this->updateChangedTime($this->entity);
    
    $this->tempStore
      ->get('TransactionForm')
      ->set('mcapi_transaction', $this->entity);
    
    $form_state->setRedirect(
      'mcapi.transaction.operation',
      ['mcapi_transaction' => 0, 'operation' => 'create']);
  }

}

