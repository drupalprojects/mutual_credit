<?php
//shared function between intertrading client and server

/*
 * All requests in this API return OK or a message
 * This wrapper filters out the ok and only returns error messages
 */
//requests the 2ndclient and parses the results
//keeps them in a format that can be forwarded to the first client
function intertrading_json_request($url, $params) {
  $result = drupal_http_request(
    $url,
    array(
      'method' => 'POST',
      'data' => http_build_query($params)
    )
  );
  $return = new stdClass;
  $data = drupal_json_decode($result->data);
  if (count($data) && is_array($data)) {
    $return->code = array_shift($data);
    $return->args = $data;
    if ($result->code == 200) {//valid intertrading result
      //now handle intertrading errors
      if ($return->code == 'OK') {
        
      }
      else {
        $return->message = intertrading_error_codes($return->code, $return->args);
        watchdog('intertrading', '@message', array('@message' => $return->message));
      }
    }
    else {//this could mean a 500 error or something
      intertrading_error('Unexpected result from '.$url, $result);
      $return->message = t('Server reports:'). $result->data;
    }
  }
  //http status codes failure
  else {
    $args = array('@message' => drupal_json_decode($result->data));

    watchdog('intertrading', "Server failed to authenticate with @server; http status @status", $args);
    $return  = array(
      'code' => 27,
      'message' => intertrading_error_codes(27, $args),
      'args' => $args
    );
  }
  return (object)$return;
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
    intertrading_xss_validate($data[$fieldname], $fieldname);
  }
}

function intertrading_xss_validate($val, $fieldname) {
  $filtered = filter_xss($val, array());
  if($val != $filtered) {
    intertrading_respond(13, array('@fieldname' => $val));
  }
}


function intertrading_mimic_form_state_values($creator, $payer, $payee, $type, $quantity, $description) {
  $values = array(
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
    $values[$fieldname] = array(
      'und' => array(
        0 => array(
          'value' => $description
        )
      )
    );
  }
  return $values;
}



function intertrading_error() {
  $strings[] = '-';
  $strings[] = variable_get('site_name', 'blah') .'...'. date('D H:i:s');
  foreach (func_get_args() as $arg) {
    $strings[] = print_r($arg, 1);
  }
  file_put_contents('intertrading.msg', implode("\n", $strings)."\n\n", FILE_APPEND);
}


function intertrading_error_codes($code, $args = array()) {
  //$code 'OK' should never be passed to this function
  switch ($code) {
    //transaction
    case 1: return t("Transaction would exceed your system's max limits on server by @quantity", $args);
    case 2: return t("Transaction would exceed your system's min limits on server by @quantity", $args);
    case 3: return t("Transaction would exceed dest max limits on server by @quantity");
    case 4: return t("Transaction would exceed dest min limits on server by @quantity");
    case 5: return t('Problem saving transaction on server: @message', $args);
    case 6: return t('Unknown account & failed to create a new account on intertrading server');
    case 7: return t('Diagnostics from server: @message', $args);
    case 8: return t('Invalid transaction field: @fieldname: @value', $args);
    //failure to authenticate or validate handshake with server
    case 11: return t('Missing config field: @fieldname', $args);
    case 12: return t('Field @fieldname should be @operator 0: @val', $args);
    case 13: return t('Field @fieldname contains invalid characters', $args);
    case 14: return t('Your Intertrading ratio (balance/volume) exceeds @num%: @balance / @volume', $args);
    case 15: return t("Type error in field '@fieldname'. Should be a @type.", $args);
    case 16: return t("Your exchange is not permitted on the intertrading network.");
    case 17: return t("Wrong key");
    case 18: return t("not enough data to authenticate");
    //failure of remote client
    case 20: return t("Server failed to connect with @server; returned: @message", $args);
    case 21: return t("Transaction would exceed remote client's max intertrading limits by @quantity", $args);
    case 22: return t("Transaction would exceed remote client's min intertrading limits by @quantity, $args");
    case 23: return t("Transaction would exceed remote client's min account limits by @quantity, $args");
    case 24: return t("Transaction would exceed remote client's max account limits by @quantity", $args);
    case 25: return t("Misc validation error on remote client: @message", $args);
    case 26: return t("server not found: @server", $args);
    case 27: return $args['@message'];
    default: return t('Unknown error code @code: @args', array('@code' => $code, '@args' => print_r($args, 1)));
  }
}