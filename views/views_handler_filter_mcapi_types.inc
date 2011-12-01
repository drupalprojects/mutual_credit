<?php
// $Id: views_handler_filter_mcapi_types.inc,
/**
 * Filter handler for exchange types
 */
class views_handler_filter_mcapi_types extends views_handler_filter_many_to_one {

  function get_value_options() {
    $types = module_invoke_all('mcapi_info_types');
    $key = array_search('unspecified', $types);
    unset($types[$key]);
    foreach ($types as $type) {
      $this->value_options[$type] = $type;
    }
  }
}
