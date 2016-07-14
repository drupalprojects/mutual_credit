<?php

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Exchange;
use Drupal\Core\Form\FormStateInterface;

/**
 * Create a form for billing a user in another exchange.
 */
class RemoteTransactionBillForm extends RemoteTransactionForm {

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entity->payer->target_id = Exchange::intertradingWalletId();
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
      $form['payer']['#type'] = 'intertrading_wallet';
      $form['payee']['widget'][0]['target_id']['#title'] = $this->t('Local wallet to be credited');
      $form['outgoing'] = [
        '#type' => 'value',
        '#value' => 0,
      ];
    }
    return $form;
  }

}
