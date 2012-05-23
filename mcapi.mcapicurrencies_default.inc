<?php
/*
 * implements hook_mcapicurrencies_default
 * simply copies the currency in the default currency variable, used for single currency systems
 */
function mcapi_mcapicurrencies_default() {
  $def_name = 'def_drup';
  $currency = new stdClass;
  $currency->disabled = FALSE; /* Edit this to true to make a default currency disabled initially */
  $currency->api_version = 3;
  $currency->currcode = $def_name;
  $currency->data = (object)array(
    //default currency name is taken from the site_name at the moment it's first called
    'info' => array(//this wierd structure is for ctools benefit
      'currcode' => $def_name,
    ),
    'human_name' =>  t('Community Coins'),
    'display' => array(
      'format' => '@minus<strong>CC</strong>@integer:@subdivision',
      'divisions' => CURRENCY_DIVISION_MODE_CUSTOM,
      'divisions_setting' => "0|00\n25|15 mins\n50|30 mins\n75|45mins",
    ),
    'uid' => 1,
    'currency_access' => array(
      'membership' => 'user_access_transact',
      'user_aggregated_view' => 'user_access_transact',
      'currency_aggregated_view' => 'mcapi_access_anyone',
      'undo' => 'user_access_manage_all_transactions',
    ),
    'transaction_access' => array(
      1 => array(
        'mcapi_access_authenticated' => 'mcapi_access_authenticated',
      ),
      0 => array(),
      -3 => array(
        'mcapi_access_admin' => 'mcapi_access_admin',
      ),
      -1 => array(
        'mcapi_xacc_participants' => 'mcapi_xacc_participants',
        'mcapi_access_admin' => 'mcapi_access_admin',
      ),
    ),
    'zero' => 0,
  );

  $currencies[$def_name] = $currency;
  return $currencies;
}
