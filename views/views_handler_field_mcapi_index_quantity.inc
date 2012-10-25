<?php
// $Id:

/**
 * @file
 * Field handler to show quantities IN THE INDEX TABLE
 * NB this doesn't apply to the worth_field linked to the mcapi_transactions table, but to the mcapi_index table only.
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_index_quantity extends views_handler_field {

  function query() {
    $this->ensure_my_table();
    //increment the name of the alias in case this field is used more than once in the same display
    static $i;
    $i++;
    $params = $this->options['group_type'] != 'group' ? array('function' => $this->options['group_type']) : array();
    $alias = 'quantity_'.$i;
    $this->field_alias = $this->query->add_field($this->table_alias, $this->real_field, $alias, $params);
  }

  function render($values) {
    return array(
      '#theme' => 'worth_field',
      '#currcode' => $values->currcode,
      '#quantity' => $values->{$this->field_alias}
    );
  }
}
