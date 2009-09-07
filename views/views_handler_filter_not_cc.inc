<?php

class views_handler_filter_not_cc extends views_handler_filter_boolean_operator{
  function construct() {
    parent::construct();
    $this->value_value = t('This is paid for with official currency');
  }

  function query() {
    $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field " . (empty($this->value) ? '!=' : '=') . TRUE);
  }
}