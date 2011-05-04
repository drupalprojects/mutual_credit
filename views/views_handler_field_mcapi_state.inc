<?php
//Id;

class views_handler_field_mcapi_state extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();

    return $options;
  }


  function render($values) {
    static $lookup;
    $lookup = module_invoke_all('transaction_info', 'states');

    $value = $this->get_value($values);
    return $lookup[$value];
  }
}