<?php


class views_handler_field_mcapi_limits extends views_handler_field {

  function option_definition() {
    $currencies = &drupal_static('currencies');
    $options = parent::option_definition();
    $options['currcode'] = array('default' => key($currencies));
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $currencies = &drupal_static('currencies');
    module_load_include('inc', 'mcapi');
    $form['currcode'] = currency_picker_element(array_keys($currencies), $this->options['currcode']);
    parent::options_form($form, $form_state);
  }

  function query() {
    //parent::query();
    $this->ensure_my_table();
    //$this->add_additional_fields(array('uid'));
  }

  //returns a render array
  function render($values) {
    module_load_include('inc', 'mcapi_limits');
    $currcodes = is_array($this->options['currcode']) ? $this->options['currcode'] : array($this->options['currcode']);
    return show_balance_limits($values->uid, $currcodes);
  }
}
