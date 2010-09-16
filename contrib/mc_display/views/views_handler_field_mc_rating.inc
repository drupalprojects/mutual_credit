<?php
// $Id$

/**
 * @file
 * Field handler to allow linking to a payee's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mc_rating extends views_handler_field {

  function construct() {
    parent::construct();
    $this->additional_fields['cid'] = 'cid';
  }
  function render($values) {
    return theme('mc_rating', $values->{$this->field_alias}, $values->{$this->aliases['cid']});
  }
}
