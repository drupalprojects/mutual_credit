<?php
// $Id: 
/**
 *  Argument handler to select exchanges involving the passed user
 */
class views_handler_argument_mcapi_participant extends views_handler_argument_numeric {
  function construct() {
    parent::construct();
    $this->value_value = t('Is the logged in user');
  }
    /**
   * Override the behavior of title(). Get the name of the user.
   * taken from views_handler_argument_user_uid.inc
   */
  function title_query() {
    if (!$this->argument) {
      return array(variable_get('anonymous', t('Anonymous')));
    }

    $titles = array();

    $result = db_query("SELECT u.name FROM {users} u WHERE u.uid IN (:uids)", array(':uids' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->name);
    }
    return $titles;
  }

  function query($group_by = FALSE) {
    $this->ensure_my_table();
    $this->query->add_where_expression(0, "$this->table_alias.payer = $this->argument OR $this->table_alias.payee = $this->argument");
  }
}
