<?php

class views_handler_field_mcapi_expenditure extends views_handler_field {

  function pre_query() {
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      $key = array_search('participant', array_keys($this->view->argument));
      if ($key !== FALSE) {
        //$this->query->add_where(NULL, 'payer', $this->view->args[$key]);
      }
    }
  }

  function query() {
    $this->ensure_my_table();
    //needed for rendering
    $this->add_additional_fields( array('field' => 'quantity'));
  }

  function render($values) {
    return mcapi_format_money($values->{$this->field_alias}, $values->mcapi_transactions_cid);
  }
}