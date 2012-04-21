<?php

class views_handler_field_mcapi_sign extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::options_form($form, $form_state);
    // The path is set by render_link function so don't allow to set it.
    $form['alter']['path'] = array('#access' => FALSE);
    $form['alter']['external'] = array('#access' => FALSE);
  }


  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields(array('xid'));
  }

  function render($values) {
    $xid = $values->{$this->aliases['xid']};
    $transaction = transaction_load($xid);
    if (!mcapi_pending_signatories_access($transaction)) return '';
    return l($this->options['text'], 'transaction/'. $xid . '/sign');
  }
  
}