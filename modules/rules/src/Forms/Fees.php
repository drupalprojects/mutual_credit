<?php

/**
 * @file
 * Contains \Drupal\mcapi_rules\Forms\Fees.
 *
 * @todo reimplement with Rules
 * When the transaction is assembling
 * If transaction is of type x
 * If transaction has value > cc 10
 * If recipient is not user 1
 * If it would transgress balance limits (validation phase)
 * Then add a child transaction in which
 *   the payee is whatever account
 *   the worth is x% of cc or fixed value
 *   tyoe is auto
 *
 */
namespace Drupal\mcapi_rules\Forms;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Fees extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mcapi_fees_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('This feature will be removed when rules integration is complete');

    $config = $this->configFactory()->get('mcapi.fees');
    $form['curr_id'] = [
      '#title' => $this->t('Currency'),
      '#type' => 'mcapi_currency_select',
      '#default_value' => $config->get('curr_id'),
      '#weight' => 1
    ];
    $form['types'] = [
      '#title' => $this->t('Transaction types'),
      '#description' => $this->t("The fees transaction will be of type 'Automated'"),
      '#type' => 'mcapi_types',
      '#default_value' => $config->get('types'),
      '#weight' => 3,
      '#multiple' => TRUE
    ];
    $form['rate'] = [
      '#title' => $this->t('Per cent'),
      '#type' => 'number',
      '#default_value' => $config->get('rate'),
      '#min' => 0,
      '#max' => 100,
      '#weight' => 5
    ];
    $form['round_up'] = [
      '#title' => $this->t('Round the calculation up'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('round_up'),
      '#weight' => 7
    ];
    $form['payers'] = [
      '#title' => $this->t('Payer of the fee'),
      '#type' => 'checkboxes',
      '#options' => [
        'payer' => $this->t("The transaction's payer"),
        'payee' => $this->t("The transaction's payee")
      ],
      'payer' => [
        '#default_value' => $config->get('payer')
      ],
      'payee' => [
        '#default_value' => $config->get('payer')
      ],
      '#weight' => 9
    ];
    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textfield',
      '#default_value' => $config->get('description'),
      '#weight' => 11
    ];
    $form['target'] = [
      '#title' => $this->t('Recipient wallet'),
      '#type' => 'wallet_entity_auto',
      '#default_value' => \Drupal\mcapi\Entity\Wallet::load($config->get('target')),
      '#weight' => 13
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('mcapi.fees');

    $config
      ->set('curr_id', $form_state->getValue('curr_id'))
      ->set('rate', $form_state->getValue('rate'))
      ->set('types', $form_state->getValue('types'))
      ->set('round_up', $form_state->getValue('round_up'))
      ->set('payer', $form_state->getValue('payers')['payer'])
      ->set('payee', $form_state->getValue('payers')['payee'])
      ->set('target', $form_state->getValue('target'))
      ->save();

    parent::submitForm($form, $form_state);
  }


  protected function getEditableConfigNames() {
    return ['mcapi.fees'];
  }

}
