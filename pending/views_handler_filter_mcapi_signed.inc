kkkkkkkkkkkkk<?php

//this is a virtual field, so it just needs ensure_my_table... does it?
class views_handler_filter_mcapi_signed extends views_handler_filter_boolean_operator {

  function query() {
    parent::query();
    $this->ensure_my_table();
  }

}
