<?php
/*
 * implements hook_default_mcapi_currencies
 * simply copies the currency in the default currency variable, used for single currency systems
 */
function mcapi_currencies_default_mcapicurrencies() {
  $currency = new stdClass;
  $currency->disabled = FALSE; /* Edit this to true to make a default currency disabled initially */
  $currency->api_version = 3;
  $currency->data = (object)variable_get('currency_default', 'ddd');
  $currency->currcode = $currency->data->info['currcode'];

  $currencies[$currency->currcode] = $currency;
  return $currencies;
}
