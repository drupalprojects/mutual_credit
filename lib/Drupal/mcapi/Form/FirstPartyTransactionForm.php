<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\FirstPartyTransactionForm.
 * Generate a Transaction form using the FirstParty_editform entity.
 */

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Form\TransactionForm;


class FirstPartyTransactionForm extends TransactionForm {


  /**
   * Inherit
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    //TODO
    //this is the only way I know how to get the args. Could it be more elegant?
    $form_id = \Drupal::request()->attributes->get('_raw_variables')->get('form_id');
    $entity = \Drupal::config('mcapi.1stparty.'.$form_id);
debug($entity->get('title'));
debug($entity->get('id'));

    return $form;
  }






}

