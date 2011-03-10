<?php
// $Id: views_handler_filter_mcapi_currency.inc,v 1.1.2.2 2010/12/22 20:21:51 matslats Exp $
/**
 * Simple filter to handle matching of multiple options selectable via checkboxes
 *
 * Definition items:
 * - numeric: If set to true, this item will use numeric operators instead of string.
 *
 */
class views_handler_filter_mcapi_currency extends views_handler_filter_in_operator {
  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }
    $currencies = currencies_load();
    foreach ($currencies as $currency) {
      $this->value_options[$currency->nid] = $currency->title;
    }
  }
}
