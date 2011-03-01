<?php
// $Id: views_handler_argument_mc_user_exchanges.inc,v 1.1.2.2 2010/12/22 20:21:51 matslats Exp $

/**
 * Filter handler for the current user
 */
class views_handler_argument_mc_user_exchanges extends views_handler_argument_numeric {
  function construct() {
    parent::construct();
    $this->value_value = t('Is the logged in user');
  }
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
  }

function query() {
    $this->ensure_my_table();
    $this->query->add_where(0, "$this->table_alias.payer_uid = %d OR $this->table_alias.payee_uid = %d", $this->argument, $this->argument);
  }
}
