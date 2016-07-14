<?php

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Exchange;
use Drupal\Core\Form\FormStateInterface;

/**
 * Create a form for crediting a user in another exchange.
 */
class RemoteTransactionCreditForm extends RemoteTransactionForm {

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);

    $this->entity->payee->target_id = Exchange::intertradingWalletId();

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
      $form['remote_user_id']['#title'] = $this->t('Id of remote seller to credit');
      $form['payee']['#type'] = 'intertrading_wallet';
      $form['payer']['widget'][0]['target_id']['#title'] = $this->t('Local wallet to be billed');
      $form['outgoing'] = [
        '#type' => 'value',
        '#value' => 1,
      ];
      $form['amount'] = [
        '#title' => t('Remote quantity'),
        '#description' => t("Denominated in the OTHER exchange's currency"),
        '#type' => 'textfield',
        '#element_validate' => [[get_class($this), 'validateAmount']],
        '#default_value' => 0,
        '#size' => 5,
        '#weight' => 5,
      ];
      unset($form['worth']);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    $names = parent::getEditedFieldNames($form_state);
    $key = array_search('worth', $names);
    unset($names[$key]);
    return $names;
  }

  /**
   * Form element validation callback.
   */
  public static function validateAmount(&$element, $form_state) {
    if (is_numeric($element['#value'])) {
      if ($element['#value'] > 0) {
        return;
      }
    }
    $form_state->setError($element, t('Quantity must be a positive number'));
  }

}
