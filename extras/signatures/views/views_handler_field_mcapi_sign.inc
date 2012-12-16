<?php

class views_handler_field_mcapi_sign extends views_handler_field {

  function construct() {
    parent::construct();
    $this->additional_fields['serial'] = 'serial';
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => t('Sign'), 'translatable' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
      '#required' => TRUE
    );
    parent::options_form($form, $form_state);
    // The path is set by render_link function so don't allow to set it.
    $form['alter']['path'] = array('#access' => FALSE);
    $form['alter']['external'] = array('#access' => FALSE);
  }


  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields(array('serial'));
  }

  function render($values) {
    $serial = &$values->{$this->aliases['serial']};
    $transaction = transaction_load($serial);
    if (transaction_sign_access($transaction)) {
      return l($this->options['text'], 'transaction/'. $serial . '/sign');
    }
    elseif (transaction_signoff_access($transaction)) {
      return l(t('Sign off'), 'transaction/'. $serial . '/signoff');
    }
  }

}