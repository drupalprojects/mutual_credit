<?php
// $Id$

/**
 * @file
 * Field handler to allow linking to a payee's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mc_amount extends views_handler_field {

  function construct() {
    parent::construct();
    $this->additional_fields['cid'] = 'cid';
    if ($this->definition['group'] == t('Exchanges')) {
      $this->additional_fields['payer_uid'] = 'payer_uid';
      $this->additional_fields['payee_uid'] = 'payee_uid';
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['output_type'] = array('default' => 'normal');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $options = array(
      'incoming' => t('Show only incoming'),
      'outgoing' => t('Show only outgoing'),
      'sign' => t('Change sign to minus'),
      'normal' => t('Just show amount')
    );

    if ($this->definition['group'] == t('Exchanges')) {
      $form['output_type'] = array(
        '#type' => 'radios',
        '#title' => t('Output type'),
        '#description' => t('When a user arguments is passed, this option is relative to that user, otherwise, ignored'),
        '#options' => $options,
        '#default_value' => $this->options['output_type'],
      );
    }
  }

  function render($values) {
    //if we are using the 'user_exchanges' argument, the options kick in.
    //not the most efficient way to access the arguments
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      $key = array_search('user_exchanges', array_keys($this->view->argument));
      $uid = $this->view->args[$key];
      switch($this->options['output_type']) {
        case 'outgoing':
          if ($values->{$this->aliases['payee_uid']} == $uid) return;
          break;
        case 'incoming':
          if ($values->{$this->aliases['payer_uid']} == $uid) return;
          break;
        case 'sign':
          if ($values->{$this->aliases['payer_uid']} == $uid) {
            //negate the value
            $values->{$this->field_alias} = -$values->{$this->field_alias};
          }
      }
    }
    elseif ($this->options['output_type'] != 'normal') {
      return t('n/a');
    }
    return theme('money', $values->{$this->field_alias}, $values->{$this->aliases['cid']});
  }
}