<?php
//shared function between intertrading client and server

//this is the definitive list of incoming fields.
function intertrading_handshake_fields() {
  //each element states its type, for validation, whether it is required, and an optional argument for the validator
  return array(
    'src_url' => array('url', TRUE),
    'mail' => array('mail', TRUE),
    'ticks' => array('float', TRUE, '>'),
    'api' => array('api', TRUE),
    'name' => array('string', TRUE, NULL),
    'visibility' => array('toggle', FALSE), //defaults to off
    'logo' => array('string', FALSE), //some kind of validation would be nice
    'lat' => array('float', FALSE),
    'lon' => array('float', FALSE),
    'new_key' => array('key', FALSE)
  );
}

function intertrading_report_fields() {
  return array(
    'traders' => array('integer', TRUE, '>'),
    'transactions' => array('integer', TRUE, '>='),
    'volume' => array('float', TRUE, '>='),
    'deficit' => array('float', TRUE, '<='),
    'balance' => array('float', TRUE),
    'integrity' => array('toggle', TRUE, '>')
  );
}
/*
 * All requests in this API return OK or a message
 * This wrapper filters out the ok and only returns error messages
 */
function intertrading_json_request($url, $params, &$return) {
  intertrading_error($url, $params);
  $result = drupal_http_request(
    $url,
    array(
      'method' => 'POST',
      'data' => http_build_query($params)
    )
  );
  if ($result->code == 200) {
    $data = drupal_json_decode($result->data);
    if (is_array($data)) {
      $code = array_shift($data);
      if ($code != 'OK') {
        $return = intertrading_error_codes($code, $data);
        watchdog('intertrading', '@message', array('@message' => $return));
        return $code;
      }
      $return = $data;
    }
    return;
  }
  elseif ($result->code == -1) {
    watchdog('intertrading', 'No response from http request to: @url', array('@val' => $url));
    $return = t("No response from http request to: @url", array('@val' => $url));
  }
  else {
    $return = $result->status_message;
  }
  return $result->code;
}

function intertrading_error_codes($code, $args = array()) {
  switch ($code) {
    //transaction
    case 1: return t("Transaction would exceed your system's max limits on server by @quantity", $args);
    case 2: return t("Transaction would exceed your system's min limits on server by @quantity", $args);
    case 3: return t("Transaction would exceed dest max limits on server by @quantity");
    case 4: return t("Transaction would exceed dest min limits on server");
    case 5: return t('Problem saving transaction on server');
    case 6: return t('Unknown account & failed to create a new account on intertrading server');
    case 7: return 'Diagnostics from server: '.$args['@message'];
    case 564: return t('Either payer_url or payee_url must be blank, or set to your url');
    //failure to authenticate or validate handshake with server
    case 11: return t('Missing config field: @fieldname', $args);
    case 12: return t('Field @fieldname should be @rel 0: $val', $args);
    case 13: return t('Field @fieldname contains invalid characters', $args);
    case 14: return t('Your Intertrading ratio (balance/volume) exceeds @num%: @balance / @volume', $args);
    case 15: return t('Type error in field @fieldname. Should be a @type.', $args);
    //failure of remote client
    case 20: return t("server failed to authenticate with remote client");
    case 21: return t("Transaction would exceed remote client's max intertrading limits");
    case 22: return t("Transaction would exceed remote client's min intertrading limits");
    case 23: return t("Transaction would exceed remote client's min account limits");
    case 24: return t("Transaction would exceed remote client's max account limits");
    case 25: return t("Misc validation error on remote client");
  }
}

function intertrading_get_post() {
  if (count($_POST)) return $_POST;
  if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) return drupal_get_query_array($GLOBALS['HTTP_RAW_POST_DATA']);
  return array();
}


function intertrading_respond($code, $data = array()) {
  drupal_add_http_header('Status', "200 Intertrading");
  array_unshift($data, $code);
  drupal_json_output($data);
  die();
}

function intertrading_error() {
  foreach (func_get_args() as $arg) {
    $strings[] = print_r($arg, 1);
  }
  file_put_contents('intertrading.msg', implode("\n", $strings)."\n\n", FILE_APPEND);
  //intertrading_respond(7, array('@message' => implode("<br />", $strings)));
}


function intertrading_validate_transaction_post(&$data) {
  if (!is_numeric($data['quantity'])) {
    intertrading_respond(8,  array('@value' => $data['quantity'], '@fieldname' => 'quantity'));
  }
  if (!in_array($data['src_url'], array($data['src_url'], $data['payee_url']))) {
    intertrading_respond(8,  array('@value' => $data['quantity'], '@fieldname' => 'payer_url, payee_url'));
  }
  $fields = array('payer', 'payee', 'quantity');
  if (variable_get('intertrading_description_required', 1)) {
    $fields[] = 'description';
  }
  foreach ($fields as $fieldname) {
    if (empty($data[$fieldname])) {
      intertrading_respond(8,  array('@value' => $data[$field], '@fieldname' => $fieldname));
    }
    intertrading_xss_validate($data[$field], $fieldname);
  }
}

function intertrading_xss_validate($val, $fieldname) {
  $filtered = filter_xss($val, array());
  if($val != $filtered) {
    intertrading_respond(13, array('@fieldname' => $val));
  }
}


function intertrading_convert_to_entity($creator, $payer, $payee, $type, $quantity, $description) {
  $transaction = (object)array(
    'creator' => $creator,
    'state' => TRANSACTION_STATE_FINISHED,
    'payer' => $payer,
    'payee' => $payee,
    'type' => $type,
    'worth' => array(
      'und' => array(
        0 => array(
          'quantity' => $quantity,
          'currcode' => key(currencies_load())
        )
      )
    ),
  );
  if ($fieldname = variable_get('intertrading_description_field', '')) {
    $transaction->$fieldname = array(
      'und' => array(
        0 => array(
          'value' => $description
        )
      )
    );
  }
  return $transaction;
}