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
    'accounting' => array(
      'update_mode' => 1,
      'erase_mode' => 1
    ),
    'uid' => 1,
    'access' => array(
      'membership' => 'mcapi_can_transact',
      'user_aggregated_view' => 'mcapi_can_transact',
      'currency_aggregated_view' => 'mcapi_can_transact',
    ),
    'view' => array(
      1 => 'mcapi_access_participants_admin',
      0 => 'mcapi_access_authenticated',
      -1 => 'mcapi_access_participants_admin',
      -2 => 'mcapi_access_admin'
    ),
    'update' => array(
      1 => 'mcapi_access_creator',
      0 => 'mcapi_access_none',
      -1 => 'mcapi_access_creator',
      -2 => 'mcapi_access_admin'
    ),
    'erase' => array(
      1 => 'mcapi_access_none',
      0 => 'mcapi_access_none',
      -1 => 'mcapi_access_creator',
      -2 => 'mcapi_access_admin'
    ),
    'zero' => 0,
  );

  $currencies[$def_name] = $currency;
  return $currencies;
}
