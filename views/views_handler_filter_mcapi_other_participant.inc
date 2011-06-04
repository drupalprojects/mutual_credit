<?php
// $Id: views_handler_filter_mcapi_other_participant.inc,

/**
 * Allows the user to filter only by the currencies they have permission to use.
 */
class views_handler_filter_mcapi_other_participant extends views_handler_filter_user_name {

  function init(&$view, &$options) {
    parent::init($view, $options);
    $this->options['exposed'] = TRUE;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['expose_button']['checkbox']['checkbox']['#disabled'] = TRUE;
  }

  function query() {
    $this->ensure_my_table();
    $or = db_or()
      ->condition('payer', $this->value)
      ->condition('payee', $this->value);
    $this->query->add_where($this->options['group'], $or);
  }
}
