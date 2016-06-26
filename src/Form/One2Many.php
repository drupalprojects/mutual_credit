<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder to pay from one wallet to many wallets.
 */
class One2Many extends MassPayBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mcapi_12many';
  }

  /**
   * {@inheritdoc}
   */
  public function step1(array &$form, FormStateInterface $form_state) {
    parent::step_1($form, $form_state);
    if (empty($form_state->get('confirmed'))) {
      $form['payer']['#weight'] = 1;
      $form['mode']['#weight'] = 2;
      $form['payee']['#weight'] = 3;
      $form['worth']['#weight'] = 4;
      $form['description']['#weight'] = 5;
      $form['payer']['#title'] = $this->t('The one payer');
      unset($form['payer']['#selection_settings']);
      $form['payee']['#title'] = $this->t('The many payees');
      $form['payee']['#tags'] = TRUE;
      $form['mode']['#title'] = $this->t('Will pay');
    }
  }

}
