<?php

/*
 * implements hook_mcapicurrencies_default
 * simply copies the currency in the default currency variable, used for single currency systems
 */
function mcapi_mcapicurrencies_default() {
  $currency = new stdClass;
  $currency->disabled = FALSE; /* Edit this to true to make a default currency disabled initially */
  $currency->api_version = 3;
  $currency->currcode = DEFAULT_CURRENCY;
  $currency->data = (object)array(
    //default currency name is taken from the site_name at the moment it's first called
    'info' => array(//this wierd structure is for ctools benefit
      'currcode' => DEFAULT_CURRENCY,
    ),
    'human_name' =>  t('Community Coins'),
    'display' => array(
      'format' => '@minus<strong>CC</strong>@integer:@subdivision',
      'divisions' => CURRENCY_DIVISION_MODE_CUSTOM,
      'divisions_setting' => "0|00\n25|15 mins\n50|30 mins\n75|45mins",
      'zero' => '',
    ),
    'currency_access' => array(
      'membership' => 'transact',
      'user_aggregated_view' => 'transact',
      'currency_aggregated_view' => 'transact',
    ),
    'transaction_view_access' => array(
      TRANSACTION_STATE_FINISHED => array(
        'transaction_access_callback_perm_transact' => 'transaction_access_callback_perm_transact',
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
      TRANSACTION_STATE_UNDONE => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
    ),
    'transaction_operation_access' => array(
      'undo' => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
      'sign' => array(
        'transaction_access_callback_pending_signatory' => 'transaction_access_callback_pending_signatory'
      ),
      'undo_pending' => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
      'signoff' => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      )
    ),
    'uid' => '1',
    'reservoir' => '1',
    'limits' => array(
      'limits_callback' => 'limits_global',
      'limits_global' => array(
        'min' => '0',
        'max' => '',
      ),
      'personal' => 0,
      'skip' => array(
        'reservoir' => 0,
        'auto' => 'auto',
        'mass' => 'mass',
        'user1' => 0,
        'owner' => 0,
      ),
    )
  );
  $currencies[DEFAULT_CURRENCY] = $currency;



  $mcapicurrency = new stdClass();
  $mcapicurrency->disabled = FALSE; /* Edit this to true to make a default mcapicurrency disabled initially */
  $mcapicurrency->api_version = 3;
  $mcapicurrency->currcode = 'national';
  $mcapicurrency->data = (object) array(
    'info' => array(
      'currcode' => 'national',
    ),
    'human_name' => t('Euros'),
    'display' => array(
      'format' => '@minus€@integer:@subdivision',
      'divisions' => CURRENCY_DIVISION_MODE_CENTS_INLINE,
      'divisions_setting' => '',
    ),
    'currency_access' => array(
      'membership' => 'transact',
      'user_aggregated_view' => 'transact',
      'currency_aggregated_view' => 'transact',
    ),
    'transaction_view_access' => array(
      TRANSACTION_STATE_FINISHED => array(
        'transaction_access_callback_perm_transact' => 'transaction_access_callback_perm_transact',
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
      TRANSACTION_STATE_UNDONE => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
    ),
    'transaction_operation_access' => array(
      'undo' => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
      'sign' => array(
        'transaction_access_callback_pending_signatory' => 'transaction_access_callback_pending_signatory'
      ),
      'undo_pending' => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      ),
      'signoff' => array(
        'transaction_access_callback_perm_manage_all' => 'transaction_access_callback_perm_manage_all'
      )
    ),
    'uid' => '1',
    'reservoir' => '1',
    'limits' => array(
      'limits_callback' => 'limits_global',
      'limits_global' => array(
        'min' => '0',
        'max' => '',
      ),
      'personal' => 1,
      'skip' => array(
        'reservoir' => 'reservoir',
        'auto' => 0,
        'mass' => 0,
        'user1' => 0,
        'owner' => 0,
      ),
    )
  );
  $currencies[$mcapicurrency->currcode] = $mcapicurrency;

  return $currencies;
}
