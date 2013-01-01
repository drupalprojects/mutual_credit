<?php

/*
 * implements hook_mcapicurrencies_default_alter
 */
function mcapi_signatures_mcapicurrencies_default_alter(&$currencies) {
  foreach ($currencies as $name => $currency) {
    $currencies[$name]->data->transaction_view_access[TRANSACTION_STATE_PENDING] = drupal_map_assoc(
      array('transaction_view_access_callback_signatory', 'transaction_view_access_callback_perm_manage_all')
    );
  }
}