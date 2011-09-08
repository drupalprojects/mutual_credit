<?php
// $Id:

/**
 * @file
 * Field handler to allow linking to a payee's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mc_running_balance extends views_handler_field {

  function query() {
    $this->ensure_my_table();
    //because only finished transations count
    $this->add_additional_fields(array('state'));
  }

  function render($values) {
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      if ($values['state'] != EXCHANGE_STATE_FINISHED) return;

      $key = array_search('participant', array_keys($this->view->argument));
      if ($key !== FALSE) {
        $uid = $this->view->args[$key];
        $quantity = mcapi_controller('transaction', 'get_running_balance', array($values['xid'], $uid,  $values->mc_exchanges_currcode));
        return theme('worth_field', array('worth' => array('quantity' => $quantity, 'currcode' => $values->mc_exchanges_currcode)));
      }
    }
  }
}
