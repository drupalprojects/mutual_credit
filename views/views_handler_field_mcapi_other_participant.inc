<?php
// $Id

/**
 * @file
 * Field handler to show the user not referenced in the views argument
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_other_participant extends views_handler_field {

  function options(&$options) {
    parent::options($options);
    $options['link_to_user'] = TRUE;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    if ($this->definition['group'] == t('Exchanges')) {
      $form['link_to_user'] = array(
        '#title' => t("Link this field to the user"),
        '#type' => 'checkbox',
        '#default_value' => $this->options['link_to_user'],
      );
    }
  }

  function render_link($data, $values) {
    $name = format_username(user_load($data));
    if (!$this->options['link_to_user']) {
      $name = strip_tags($name);
    }
    return $name;
  }
  
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      $key = array_search('participant', array_keys($this->view->argument));
      if ($key !== FALSE) {
        $uid = $this->view->args[$key];
        $result = $values->mcapi_transactions_payee == $uid ?
          $values->mcapi_transactions_payer :
          $values->mcapi_transactions_payee;
        return $this->render_link($result, $values);
      }
    }
    return '-';
  }
}
