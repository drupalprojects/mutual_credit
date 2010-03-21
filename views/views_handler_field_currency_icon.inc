<?php
// $Id$

/**
 * @file
 * Field handler to provide simple renderer that allows using a themed user link
 */
class views_handler_field_currency_icon extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['nid'] = 'nid';
    $this->additional_fields['title'] = 'title';
  }

  function render($values) {
    return '<img src="'. $values->{$this->field_alias} .'" />';
  }
}
