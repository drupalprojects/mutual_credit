<?php

class views_handler_filter_goods_not_services extends views_handler_filter_boolean_operator{
  function construct() {
    parent::construct();
    $names = variable_get ('cc_transaction_types', array());
    $this->value_value = t('This is a @good not a @service', array('@good'=> $names['offers_wants']['good'], '@service' => $names['offers_wants']['service']));
  }

  function query() {
    $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field " . (empty($this->value) ? '!=' : '=') . TRUE);
  }
}