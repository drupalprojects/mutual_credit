<?php
// $Id:

/**
 * @file
 * Field handler to allow linking to a payee's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_balance extends views_handler_field {
  function query() {
    $this->ensure_my_table();
    //because only finished transations count
    $this->add_additional_fields(array('state'));
  }

  function render($values) {
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      $key = array_search('participant', array_keys($this->view->argument));
      if ($key !== FALSE) {
        $uid = $this->view->args[$key];
        $currcode = $values->field_worth[0]['raw']['currcode'];
        return array(
          '#theme' => 'worth_field',
          '#quantity' => mcapi_user_balance($uid, $currcode, array(TRANSACTION_STATE_FINISHED), $values->xid),
          '#currcode' => $currcode
        );
      }
    }
  }
}