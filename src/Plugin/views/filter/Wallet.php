<?php

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for wallet ids.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("wallet_name")
 */
class Wallet extends InOperator {

  /**
   * Options form subform for setting options.
   *
   * @see buildOptionsForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#title' => $this->t('Wallet names....'),
      '#type' => 'wallet_entity_auto',
      '#default_value' => $this->value ? Wallet::loadMultiple($this->value) : NULL,
      '#placeholder' => $this->options['placeholder'],
    ];

    $user_input = $form_state->getUserInput();
    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      $user_input[$this->options['expose']['identifier']] = $form['value']['#default_value'];
      $form_state->setUserInput($user_input);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueValidate($form, FormStateInterface $form_state) {
    $uids = [];
    if ($values = $form_state->getValue(['options', 'value'])) {
      foreach ($values as $value) {
        $uids[] = $value['target_id'];
      }
      sort($uids);
    }
    $form_state->setValue(['options', 'value'], $uids);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if ($rc = parent::acceptExposedInput($input)) {
      // If we have previously validated input, override.
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }
    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];
    $input = $form_state->getValue($identifier);

    if ($this->options['is_grouped'] && isset($this->options['group_info']['group_items'][$input])) {
      $this->operator = $this->options['group_info']['group_items'][$input]['operator'];
      $input = $this->options['group_info']['group_items'][$input]['value'];
    }

    $uids = [];
    $values = $form_state->getValue($identifier);
    if ($values && (!$this->options['is_grouped'] || ($this->options['is_grouped'] && ($input != 'All')))) {
      foreach ($values as $value) {
        $uids[] = $value['target_id'];
      }
    }

    if ($uids) {
      $this->validated_exposed_input = $uids;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    // Prevent array filter from removing our anonymous user.
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['placeholder'] = [
      '#title' => $this->t('Placeholder text'),
      '#type' => 'textfield',
      '#default_value' => $this->options['placeholder'],
      '#weight' => -20,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['placeholder']['default'] = t('Wallet name...');
    return $options;
  }

  /**
   * Value options are built into the wallet element.
   */
  public function getValueOptions() {}

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    // Set up $this->valueOptions for the parent summary.
    $this->valueOptions = [];
    if ($this->value) {
      $result = entity_load_multiple_by_properties('wallet', ['wid' => $this->value]);
      foreach ($result as $wallet) {
        $this->valueOptions[$wallet->id()] = $wallet->label();
      }
    }
    return parent::adminSummary();
  }

}
