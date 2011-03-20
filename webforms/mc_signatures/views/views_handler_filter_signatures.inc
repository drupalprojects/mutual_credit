<?php
// $Id: views_handler_field_mc_signatories.inc,v 1.1.2.1 2010/12/06 13:19:46 hutch Exp $

/**
 * Field handler to present a link node edit.
 */
class views_handler_filter_signatures extends views_handler_filter_boolean_operator {
  /*
  function construct() {
    parent::construct();
    $this->additional_fields['nid'] = 'nid';
  }*/

  function get_value_options() {
    $this->value_options = array(
      'user' => t('User must sign'),
      'another' => t('Another must sign')
    );
  }
  function option_definition() {
    $options['value']['default'] = 'user';
    return $options;
  }

  function query() {
    //this only works if the 'participant' argument is present.
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      $key = array_search('participant', array_keys($this->view->argument));
      if ($key !== FALSE) {
        $uid = $this->view->args[$key];
        $this->ensure_my_table();
        $fieldname = "$this->table_alias.$this->real_field ";
        if ($this->options['value'] == 'user') {
          $where = "$fieldname = $uid";
        }
        else {
           $where = "$fieldname <> $uid";
        }
        $this->query->add_where($this->options['group'], $where);
      }
    }
  }


  function render($values) {
    $exchange = node_load($values->nid); //additional field from above
    $links = array();
    mc_append_sign_links($exchange, $links);

    return theme('links', $links);
  }
}
