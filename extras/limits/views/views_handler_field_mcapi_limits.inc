<?php


class views_handler_field_mcapi_limits extends views_handler_field {

  function option_definition() {
    $currencies = currencies_load();
    $options = parent::option_definition();
    $options['currcode'] = array('default' => key($currencies));
    $options['excess'] = array('default' => FALSE, 'boolean' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $currencies = currencies_load();
    module_load_include('inc', 'mcapi');
    $form['currcode'] = currency_picker_element(array_keys($currencies), $this->options['currcode']);
    $form['excess'] = array(
      '#title' => t('Show only the excesses'),
      '#description' => t('The amount by which the limit is exceeded'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['excess']
    );
    parent::options_form($form, $form_state);
  }

  function query() {
    $this->ensure_my_table();
  }

  //returns a render array
  function render($values) {
    $currcodes = is_array($this->options['currcode']) ? $this->options['currcode'] : array($this->options['currcode']);
    if ($this->options['excess']) {
      $currcode = current($currcodes);
      if ($q = balance_limits_view_excess($values->uid, $currcode)) {
        $renderable = array('#theme' => 'worth_item', '#currcode' => $currcode, 'quantity' => $q);
        return $renderable;
      }
    }
    else return balance_limits_view($values->uid, $currcodes);
  }
}
