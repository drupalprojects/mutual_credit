<?php

namespace Drupal\mcapi_cc\Form;

use Drupal\Core\Form\FormStateInterface;

class RemoteTransactionBillForm extends RemoteTransactionForm {

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entity->payer->target_id = intertrading_wallet_id($this->exchange);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    if ($form_state->get('remote_transaction')) {
      $form = $this->confirmForm($form, $form_state);
    }
    else {
      $form = parent::form($form, $form_state);
      $form['remote_user_id']['#title'] = $this->t('Id of remote buyer to bill');
      $form['payer']['#type'] = 'value';
      $form['payer']['#value'] = intertrading_wallet_id();
      $form['payee']['widget'][0]['target_id']['#title'] = $this->t('Local wallet to be credited');
      $form['outgoing'] = [
        '#type' => 'value',
        '#value' => 0
      ];
    }
    return $form;
  }

}

