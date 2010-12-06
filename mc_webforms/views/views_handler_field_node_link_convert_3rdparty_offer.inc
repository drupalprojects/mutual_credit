<?php
// $Id$

/**
 * Field handler to present a link node edit.
 */
class views_handler_field_node_link_convert_3rdparty_offer extends views_handler_field_node_link {
  function construct() {
    parent::construct();
  }

  function render($values) {
    if (!user_access('exchange')) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('exchange...');
    return l($text, "exchange/3rdparty/offer/". $values->{$this->aliases['nid']});
  }
}
