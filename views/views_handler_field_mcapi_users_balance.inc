<?php
// $Id:

/**
 * @file
 * Given the user, this shows the balance.
 * choose ONE currency from a list of options.
 */
class views_handler_field_mcapi_users_balance extends views_handler_field {
  function query() {
    $this->ensure_my_table();
  }

  function option_definition() {
    $options = parent::option_definition();
    $def = array_shift(currencies_load());
    $options['currcode'] = array('default' => $def->info['currcode']);
    $options['total'] = array('default' => 'balance');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    if (module_exists('mcapi_currencies')) {
      $form['currcode'] = currency_picker_element($this->options['currcode'], TRUE);
    }
    $form['total'] = array(
      '#title' => t('Aggregate'),
      '#description' => t('How do you want to summarise the the transaction totals?'),
      '#type' => 'radios',
      '#options' => array(
        'balance' => t('Balance'),
        'gross_in' => t('Gross income'),
        'gross_out' => t('Gross expenditure'),
        'count' => t('Number of transactions'),
      )
    );
    parent::options_form($form, $form_state);
  }

  function render($values) {
    $currcodes = is_array($this->options['currcode']) ? $this->options['currcode'] : array($this->options['currcode']);
    $key = $this->options['total'];
    $elements = array('#theme' => 'worth_fields');
    foreach ($currcodes as $currcode) {
      $elements[$currcode] = array(
        '#theme' => 'worth_field',
        '#quantity' => transaction_totals($values->uid, $currcode)->$key,
        '#currcode' => $currcode
      );
    }
    return $elements;
  }
}