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
    'zero' => 0,
    'currency_access' => array(
      'membership' => 'user_segments_transact',
      'user_aggregated_view' => 'user_segments_transact',
      'currency_aggregated_view' => 'user_segments_all',
      'undo' => 'user_segments_manage_all_transactions',
    ),
    'transaction_access' => array(
      TRANSACTION_STATE_FINISHED => array(
        'mcapi_access_authenticated' => 'transaction_access_authenticated',
      ),
      TRANSACTION_STATE_ERASED => array(),
      TRANSACTION_STATE_REVERSED => array(),
    )
  );
  if (module_exists('mcapi_signatures')) {
    $currency->data->transaction_access[TRANSACTION_STATE_PENDING] = array();
  }

  $currencies[DEFAULT_CURRENCY_NAME] = $currency;
  return $currencies;
}
