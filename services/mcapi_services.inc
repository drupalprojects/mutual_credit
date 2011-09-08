<?php

/*
 * transaction services
 */
//maybe this could be skipped in favour of exposing transaction_load directly
function mcapi_service_transaction_retrieve($xid){
  return transaction_load($xid);
}
function mcapi_service_transaction_create($data) {
  mcapi_service_validate_object($data, 'transaction');
  $data['transaction_type'] = 'external_'. $data['transaction_type'];
  return mcapi_service_transaction_save($data);
}
function mcapi_service_transaction_update($data) {
  mcapi_service_validate_object($data, 'transaction');
  return mcapi_service_transaction_save($data);
}
function mcapi_service_transaction_save($data) {
  $currency = currency_load(1);
  $transaction = (object)array(
    'payer' => array_pop(user_load_multiple(array(), array('name' => $data['payer_uuid'])))->uid,
    'payer' => array_pop(user_load_multiple(array(), array('name' => $data['payee_uuid'])))->uid,
    'transaction_value' => array(
      'und' => array(
        0 => array(
          'quantity' => $data['quantity'],
          'currcode' => $currency->currcode,
        )
      )
    ),
    'transaction_type' => $data['transaction_type'],
    'state' => TRANSACTION_STATE_FINISHED
  );
  if (isset($data['xid'])) {
    $transaction->xid = $data['xid'];
  }
  try{
    transaction_validate($transaction);
  }
  catch (Exception $e) {
    return $e;
  }
  return transaction_save($transaction);
}

function mcapi_service_transaction_delete($xid){
  $transaction = transaction_load($xid);
  transaction_erase($transaction);
}
/*
 * return the whole account history
 * This needs work
 */
/*
function mcapi_service_transaction_statement($uuid, $ucurrcode, $count){
  $account = mcapi_service_universal_lookup($uuid, 'user');
  $currency = currency_load($ucurrcode, 'currency');
  //there might be an intermediary function here, or a view could be invoked., or more parameters could be passed
  $rows = entity_get_controller('transaction')->transaction_list(array('uid' => $account->uid));
  return array_slice($rows, 0, $count);
}*/

/*
 * Balances services
 */
/*
function mcapi_service_user_balances($uuid) {
  $balances = array();
  $currencies = currencies_load();
  $account = mcapi_service_universal_lookup($uuid, 'user');
  return entity_get_controller('transaction')->get_cache($account->uid);
}*/

/*
 * Simple validation function just checks for presence of all parameters
 */
function mcapi_service_validate_object($data, $type) {
  print_r($data);
  $data = (object)$data; //this shouldn't be necessary actually becuase restserver getControllerArguments does it
  switch($type) {
    case 'currency':
      $required_keys = array('name', 'symbol', 'iso_num');
      break;
    case 'transaction':
      $required_keys = array('payer_uuid', 'payee_uuid', 'quantity', 'ucurrcode');
      break;
  }
  foreach ($required_keys as $key) {
    if (!isset($data->$key)) $missing_parameters[] = $key;
  }
  if (isset($missing_parameters)) {
    services_error(t('Missing required sub-arguments: @missing', array('@missing' => implode(', ', $missing_parameters))), 406);
  }
}
