<?php
//Id;

class views_handler_field_mcapi_state extends views_handler_field {

  function render($values) {
    static $lookup;
    $lookup = mcapi_transaction_states();
    $value = $this->get_value($values);
    return $lookup[$value];
  }
}