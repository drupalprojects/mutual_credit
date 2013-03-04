<?php
// $Id:

/**
 * @file
 */
class views_handler_field_mcapi_index_greco extends views_handler_field {

  function query() {
    $this->ensure_my_table();
  }


  function render($values) {
    return array(
      array(
        '#theme' => 'advanced_help_topic',
        '#module' => 'mcapi_index_views',
        '#topic' => 'greco'
      ),
      array(
        '#markup' => variable_get('mcapi_greco_system_health', 0)
      )
    );
  }
}
