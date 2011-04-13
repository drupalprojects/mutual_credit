<?php

/*
 * Currency services
 */
function mcapi_service_currency_create($data) {
  mcapi_service_validate_object($data, 'currency');
  if ($data->ucid) {
    services_error(t('ucid missing from POST'), 406);
  }
  $currency = (object)array(
    'name' => $data->name,
    //'body' => t('This is the default currency'),
    'uid' => 1,
    'data' => array(
      'ucid' => $data->ucid,
      'symbol' => $data->symbol,
      'iso_num' => $data->iso_num
    ),
  );
  //this will not work with fields added to the entity

  entity_get_controller('currency')->save($currency);
  return $currency;
}

function mcapi_service_currency_retrieve($ucid) {
  return mcapi_service_universal_lookup($ucid, 'currency');
}


//this will need better validation.
function mcapi_service_currency_update($ucid, $data) {
  $currency = mcapi_service_universal_lookup($ucid, 'currency');
  if (isset($data->name)) {
    $currency->name = $data->name;
  }
  if (isset($data->symbol)) {
    $currency->format = $data->format;
  }
  if (isset($data->iso_num)) {
    $currency->data['ucid'] = $ucid;
  }
  entity_get_controller('currency')->save($currency);
}

function mcapi_service_currencies_list() {
  $currencies = currencies_load();
  foreach ($currencies as $currency) {
    if (!$currency->ucid) continue;
    $universal_currencies[$currency->ucid] = $currency;
  }
  return $universal_currencies;
}

/*
 * transaction services
 */
//maybe this could be skipped in favour of exposing transaction_load directly
function mcapi_service_transaction_retrieve($xid){
  return transaction_load($xid);
}

function mcapi_service_transaction_create($data) {
  return mcapi_service_transaction_save($data);
}
function mcapi_service_transaction_update($data) {
  return mcapi_service_transaction_save($data);
}
function mcapi_service_transaction_save($data) {
  mcapi_service_validate_object($data, 'transaction');
  $currency = mcapi_service_universal_lookup($data->ucid, 'user');
  $transaction = (object)array(
    'payer_uid' => mcapi_service_universal_lookup($data->payer_uuid, 'user'),
    'payer_uid' => mcapi_service_universal_lookup($data->payee_uuid, 'user'),
    'quantity' => $data['quantity'],
    'cid' => $currency->cid,
    'transaction_type' => 'external',
    'description' => $data->description,
    'state' => TRANSACTION_STATE_COMPLETED
  );
  if (isset($data['xid'])) {
    $transaction->xid = $data['xid'];
  }
  try{
    mcapi_transaction_validate($transaction);
  }
  catch (Exception $e) {
    return $e;
  }
  entity_get_controller('transaction')->save($transaction);
}

function mcapi_service_transaction_delete($xid){
  $transaction = transaction_load($xid);
  entity_get_controller('transaction')->erase($transaction);
}
/*
 * return the whole account history
 * This needs work
 */
function mcapi_service_transaction_statement($uuid, $ucid, $count){
  $account = mcapi_service_universal_lookup($uuid, 'user');
  $currency = mcapi_service_universal_lookup($ucid, 'currency');
  //there might be an intermediary function here, or a view could be invoked., or more parameters could be passed
  $rows = entity_get_controller('transaction')->transaction_list(array('uid' => $account->uid));
  return array_slice($rows, 0, $count);
}

/*
 * Balances services
 */
function mcapi_service_user_balances($uuid) {
  $balances = array();
  $currencies = currencies_load();
  $account = mcapi_service_universal_lookup($uuid, 'user');
  return entity_get_controller('transaction')->get_cache($account->uid);
}

/*
 * Simple validation function just checks for presence of all parameters
 */
function mcapi_service_validate_object($data, $type) {
  $data = (object)$data; //this shouldn't be necessary actually becuase restserver getControllerArguments does it
  switch($type) {
    case 'currency':
      $required_keys = array('name', 'symbol', 'iso_num');
      break;
    case 'transaction':
      $required_keys = array('payer_uuid', 'payee_uuid', 'quantity', 'ucid');
      break;
  }
  foreach ($required_keys as $key) {
    if (!isset($data->$key)) $missing_parameters[] = $key;
  }
  if (isset($missing_parameters)) {
    services_error(t('Missing required sub-arguments: @missing', array('@missing' => implode(', ', $missing_parameters))), 406);
  }
}