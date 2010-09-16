<?php
// $Id$
/**
 * Field handler to present a link node edit.
 */
class views_handler_field_mc_signatories extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['nid'] = 'nid';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    module_load_include('inc', 'mc_webforms');
    $exchange = node_load($values->{$this->aliases['nid']});
    mc_append_sign_links($exchange, $links);

    return theme('links', $links);
  }
}

