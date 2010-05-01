<?php
// $Id$

/**
 * @file
 * Filter handler for the current user
 */
class views_handler_filter_role_current_user extends views_handler_filter {

  function query() {
    global $user;
    $rids = array_keys($user->roles);
    unset($rids[DRUPAL_AUTHENTICATED_RID]);
    
    $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field IN (". implode(',', $roles) .")");
  }
}