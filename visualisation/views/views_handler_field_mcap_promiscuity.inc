<?php

class views_handler_field_mcapi_promiscuity extends views_handler_field {

  function construct() {
    parent::construct();
    //needed for rendering
    $this->additional_fields['cid'] = 'cid';
  }

  function pre_query() {
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      $key = array_search('participant', array_keys($this->view->argument));
      if ($key !== FALSE) {
        $this->add_where(NULL, 'payee', $this->view->args[$key]);
      }
    }
  }

  function query() {
    //this is needed because the address of this function implies a db field which doesn't exist
    //see mc_display.views.inc $data['mc_exchanges']['participant']
    $this->ensure_my_table();
  }

  function render($values) {
    return mcapi_format_money($values->{$this->field_alias}, $values->{$this->aliases['cid']});
  }
}