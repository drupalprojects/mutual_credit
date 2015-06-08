<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\filter\Wallet.
 *
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\Element\EntityAutocomplete;
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
      '#type' => 'select_wallet',
      '#default_value' => $this->value ? Wallet::loadMultiple($this->value) : NULL
    ];

    $user_input = $form_state->getUserInput();
    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      $user_input[$this->options['expose']['identifier']] = $default_value;
      $form_state->setUserInput($user_input);
    }
  }

  protected function valueValidate($form, FormStateInterface $form_state) {
    $uids = [];
    if ($values = $form_state->getValue(array('options', 'value'))) {
      foreach ($values as $value) {
        $uids[] = $value['target_id'];
      }
      sort($uids);
    }
    $form_state->setValue(array('options', 'value'), $uids);
  }

  public function acceptExposedInput($input) {
    $rc = parent::acceptExposedInput($input);

    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }

    return $rc;
  }

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

  protected function valueSubmit($form, FormStateInterface $form_state) {
    // prevent array filter from removing our anonymous user.
  }


  // Override to do nothing.
  public function getValueOptions() { }

  public function adminSummary() {
    // set up $this->valueOptions for the parent summary
    $this->valueOptions = [];
    if ($this->value) {
      $result = entity_load_multiple_by_properties('wallet', array('wid' => $this->value));
      foreach ($result as $wallet) {
        $this->valueOptions[$wallet->id()] = $wallet->label();
      }
    }
    return parent::adminSummary();
  }

}
