<?php
// $Id: views_handler_filter_mcapi_currency.inc,v 1.1.2.2 2010/12/22 20:21:51 matslats Exp $
/**
 * Allows the user to filter only by the currencies they have permission to use.
 */
class views_handler_filter_mcapi_currency extends views_handler_filter_in_operator {
  function get_value_options() {
    $this->value_options = which_currencies($GLOBALS['user']->uid);
  }
}
