<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\Many2One.
 * Create a cluster of payments, all into one wallet
 *
 */

namespace Drupal\mcapi\Form;

class Many2One extends MassPayBase {

  private $payer;
  private $payees;

  public function getFormId() {
    return 'mcapi_many21';
  }

  public function step_1(array &$form, $form_state) {
    parent::step_1($form, $form_state);
    if (empty($form_state->get('confirmed'))) {
      $form['payee']['#title'] = $this->t('The one payee');
      $form['payee']['#weight'] = 1;
      $form['payer']['#title'] = $this->t('The many payers');
      $form['payer']['#tags'] = TRUE;
      $form['payer']['#weight'] = 3;
      $form['mode']['#title'] = $this->t('Will receive');
    }
  }


}
