<?php

/**
 * @file
 * Field handler to allow linking to a seller's name
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_transactions_payee_uid extends views_handler_field {
  /**
   * Override init function to add uid
   */
  function init(&$view, &$data) {
    parent::init($view, $data);
  }

  function options(&$options) {
    parent::options($options);
    $options['link_to_seller'] = TRUE;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_seller'] = array(
      '#title' => t("Link this field to its user, the seller's profile"),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_seller'],
    );
  }

  function render_link($data, $values) {
    if ($values -> cc_transactions_payee_uid == 0) {
      return variable_get(cc_balancing_account_name, 'Balancing account');
    }
    $account = user_load(array('uid' => $values -> cc_transactions_payee_uid));
    if (!empty($this -> options['link_to_seller'])) {
      return theme('username', $account);
    }
    else {
      return $data;
    }
  }
  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
