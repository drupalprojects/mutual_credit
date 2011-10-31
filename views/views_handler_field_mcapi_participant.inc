<?php
// $Id: 

/**
 * @file
 * Field handler to allow linking to a payer's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_particpant extends views_handler_field_mcapi_trader {
  
  function query() {
    //this is needed because the address of this function implies a db field which doesn't exist
    //see mc_display.views.inc $data['mcapi_transactions']['participant']
    $this->ensure_my_table();
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