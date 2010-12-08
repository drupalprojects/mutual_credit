<?php
// $Id$

/**
 * @file
 * Field handler to provide simple renderer that allows using a themed user link
 */
class views_handler_field_currency_icon extends views_handler_field {
  function render($values) {
    $node = node_load($values->{$this->field_alias});
    $img = theme('image', $node->icon, $node->title, $node->title);
    return l($img, 'node/'. $node->nid, array('html' => TRUE));
  }
}
