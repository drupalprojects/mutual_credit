<?php
// $Id: views_handler_field_mcapi_amount.inc,v 1.1.2.2 2010/12/22 20:21:51 matslats Exp $

/**
 * @file
 * Field handler to allow linking to a payee's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_amount extends views_handler_field {  

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
   //cid field is injected using the views alter hook in the module
   return mcapi_format_money($values->{$this->field_alias}, $values->cid);
  }
}
