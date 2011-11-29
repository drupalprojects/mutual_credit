<?php

class views_handler_filter_mcapi_signed extends views_handler_filter_boolean_operator {

  function query() {
    $this->ensure_my_table();
  }

}
