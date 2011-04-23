<?php
// $Id: views_handler_filter_mcapi_types.inc,
/**
 * Filter handler for exchange types
 */
class views_handler_filter_mcapi_types extends views_handler_filter_many_to_one {

  //this is waiting for a suitable hook function so that modules can declare their own exchange types.
  function get_value_options() {
    $info = module_invoke_all('transaction_type_info', 'types');
    foreach ($info as $type) {
      $this->value_options[$type] = $type;
    }
  }
}
