<?php

define('DEFAULT_CURRENCY_NAME', 'def_drup');
/*
 * implements hook_mcapicurrencies_default
 * simply copies the currency in the default currency variable, used for single currency systems
 */
function mcapi_mcapicurrencies_default() {
  $currency = new stdClass;
  $currency->disabled = FALSE; /* Edit this to true to make a default currency disabled initially */
  $currency->api_version = 3;
  $currency->currcode = DEFAULT_CURRENCY_NAME;
  $currency->data = (object)array(
    //default currency name is taken from the site_name at the moment it's first called
    'info' => array(//this wierd structure is for ctools benefit
      'currcode' => DEFAULT_CURRENCY_NAME,
    ),
    'human_name' =>  t('Community Coins'),
    'display' => array(
      'format' => '@minus<strong>CC</strong>@integer:@subdivision',
      'divisions' => CURRENCY_DIVISION_MODE_CUSTOM,
      'divisions_setting' => "0|00\n25|15 mins\n50|30 mins\n75|45mins",
    ),
    'uid' => 1,
    'currency_access' => array(
      'membership' => 'transact',
      'user_aggregated_view' => 'transact',
      'currency_aggregated_view' => 'transact',
    ),
    'transaction_access' => array(
      TRANSACTION_STATE_FINISHED => array(
        'transaction_access_callback_perm_transact' => 'transaction_access_callback_perm_transact',
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
      TRANSACTION_STATE_REVERSED => array(),
    )
  );
  $currencies[DEFAULT_CURRENCY_NAME] = $currency;
  return $currencies;
}


