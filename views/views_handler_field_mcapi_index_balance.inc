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
    $this->additional_fields['xid'] = 'xid';
  }

  function query() {
    $this->ensure_my_table();
    //adding the fields seems to require that $this->options['group_type'] be set
    $this->add_additional_fields();
  }

  function render($values) {
    if (empty($this->uid)) {
      $arg = array_search('uid', array_keys($this->view->argument));
      if ($arg === FALSE) {
        drupal_set_message('Views running balance requires uid argument', 'error'); return 'error';
      }
      $this->uid = $this->view->args[$arg];
    }
    return array(
      '#theme' => 'worth_field',
      '#currcode' => $values->currcode,
      '#quantity' => db_query(
        "SELECT SUM(diff) FROM {mcapi_index} WHERE uid1 = $this->uid AND xid <= :xid AND currcode = '$values->currcode'",
        array(':xid' => $values->xid)
      )->fetchField()
    );
  }
}
