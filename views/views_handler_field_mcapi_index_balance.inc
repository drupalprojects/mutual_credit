<?php
// $Id:

/**
 * @file
 * Field handler to retrieve the running balance by adding the previous transactions
 * NB this doesn't apply to the worth_field and mcapi_transactions table, only to the transaction_index table
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_index_balance extends views_handler_field {
  
  var $uid;

  function construct() {
    parent::construct();
    $this->additional_fields['serial'] = 'serial';
  }

  function query() {
    $this->ensure_my_table();
    //adding the fields seems to require that $this->options['group_type'] be set
    $this->add_additional_fields();
  }

  function render($values) {
    if (empty($this->uid)) {
      $arg = array_search('uid1', array_keys($this->view->argument));
      if (is_numeric($arg)) {
        $this->uid = $this->view->args[$arg];
      }
      else {
        drupal_set_message("Running balance requires contextual filter 'Transaction index: 1st user'", 'error', FALSE);
        return 'error';
      }
    }
    return array(
      '#theme' => 'worth_field',
      '#currcode' => $values->currcode,
      '#quantity' => db_query(
        "SELECT SUM(diff) FROM {mcapi_index} WHERE uid1 = :uid AND serial <= :serial AND currcode = :currcode",
        array(
          ':serial' => $values->{$this->aliases['serial']},
          ':uid' => $this->uid,
          ':currcode' => $values->currcode
        )
      )->fetchField()
    );
  }
}
