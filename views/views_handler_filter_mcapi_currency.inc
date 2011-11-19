<?php
// $Id: views_handler_filter_mcapi_currency.inc,
/**
 * Filter handler for exchange types
 */
class views_handler_filter_mcapi_currency extends views_handler_filter_in_operator {

  //this is waiting for a suitable hook function so that modules can declare their own exchange types.
  function get_value_options() {
    $currencies = currencies_load();
    foreach ($currencies as $currcode => $c) {
      $this->value_options[$currcode] = $c->name;
    }
  }


  function value_form(&$form, &$form_state) {
    if (empty($this->value_options)) {
      // Initialize the array of possible values for this filter.
      $this->get_value_options();
    }
    $form['value'] = array(
      '#type' => 'select',
      '#title' => t('Currency'),
      '#description' => t('Show only transactions in this currency'),
      '#options' => $this->value_options,
      '#default_value' => $this->value,
      '#multiple' => TRUE
    );
    if (!empty($this->options['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      if (!empty($form_state['exposed']) && !isset($form_state['input'][$identifier])) {
        $form_state['input'][$identifier] = $this->value;
      }
      // If we're configuring an exposed filter, add an <Any> option.
      if (empty($form_state['exposed']) || empty($this->options['required'])) {
        $any_label = variable_get('views_exposed_filter_any_label', 'new_any') == 'old_any' ? '<Any>' : t('- Any -');
        if ($form['value']['#type'] != 'select') {
          $any_label = check_plain($any_label);
        }
        $form['value']['#options'] = array('All' => $any_label) + $form['value']['#options'];
      }
    }
  }

  function expose_options() {
    parent::expose_options();
    $this->options['expose']['operator_id'] = '=';
    $this->options['expose']['label'] = t('Currency');
    $this->options['expose']['required'] = FALSE;
  }
}
