<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\One2Many.
 * Create a cluster of payments, all from one wallet
 *
 */

namespace Drupal\mcapi\Form;

class One2Many extends MassPayBase {

  private $payer;
  private $payees;

  public function getFormId() {
    return 'mcapi_12many';
  }

  public function step_1(array &$form, $form_state) {
    parent::step_1($form, $form_state);
    if (empty($form_state->get('confirmed'))) {
      $form['payer']['#title'] = $this->t('The one payer');
      $form['payer']['#weight'] = 1;
      $form['payee']['#title'] = $this->t('The many payees');
      $form['payee']['#tags'] = TRUE;
      $form['payee']['#weight'] = 3;
      $form['mode']['#title'] = $this->t('Will pay');
    }
  }


}
