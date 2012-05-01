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
  $data = json_decode($result->data, TRUE);
  if (count($data) && is_array($data)) {
    array_walk_recursive($data, 'urldecode_m');
    $return->code = array_shift($data);
    $return->args = $data;
    if ($result->code == 200) {//valid intertrading result
      //now handle intertrading errors
      if ($return->code == 'OK') {
        
      }
      else {
        $return->message = intertrading_error_codes($return->code, $return->args);
        watchdog('intertrading', '@message', array('@message' => $return->message), WATCHDOG_NOTICE);
      }
    }
    else {//this could mean a 500 error or something
      watchdog('intertrading', 'Unexpected result from @url: @result', array('@url' => $url, '@result' => print_r($result, 1)), WATCHDOG_NOTICE);
      $return->message = t('Server reports: '). $result->data;
    }
  }
  //http status codes failure
  else {
    debug($result);
    $args = array('@client' => $url, '@status' => $result->code);
    $return  = array(
      'code' => 27,
      'message' => intertrading_error_codes(27, $args),
      'args' => $args
    );
    watchdog('intertrading', "@message", array('@message' => $return['message']), WATCHDOG_ERROR);
  }
  return (object)$return;
}



function intertrading_get_post() {
  $post = array();
  if (count($_POST)) $post = $_POST;
  elseif (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
    $string = str_replace('&amp;', '&', $GLOBALS['HTTP_RAW_POST_DATA']);
    $post = drupal_get_query_array($string);
  }
  array_walk_recursive($post, 'urldecode_m');
  return $post;
}


function intertrading_respond($code, $data = array()) {
  drupal_add_http_header('Status', "200 Intertrading");
  array_unshift($data, $code);
  drupal_json_output($data);
  die();
}

function intertrading_validate_transaction_post(&$data) {
  $error = NULL;
  if (!is_numeric($data['quantity'])) {
    $error = array(8,  array('@value' => $data['quantity'], '@fieldname' => 'quantity'));
  }
  if (!in_array($data['src_url'], array($data['src_url'], $data['payee_url']))) {
    $error = array(8,  array('@value' => $data['quantity'], '@fieldname' => 'payer_url, payee_url'));
  }
  $fields = array('payer', 'payee', 'quantity');
  if (variable_get('intertrading_description_required', 1)) {
    $fields[] = 'description';
  }
  foreach ($fields as $fieldname) {
    if (empty($data[$fieldname])) {
      $error = array(8,  array('@value' => $data[$fieldname], '@fieldname' => $fieldname));
    }
    $error = intertrading_xss_validate($data[$fieldname], $fieldname);
  }
  return $error;
}

function intertrading_xss_validate($val, $fieldname) {
  $filtered = filter_xss($val, array());
  if($val != $filtered) {
    return array(13, array('@fieldname' => $val));
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
  $args = func_get_args();
  $args[] = debug_backtrace();
  foreach (func_get_args() as $arg) {
    $strings[] = print_r($arg, 1);
  }
  //watchdog('intertrading error', '!message', array('!message' => '<pre>'.implode('<br />', $strings).'</pre>'), WATCHDOG_DEBUG);
  file_put_contents('intertrading.msg', implode("\n", $strings)."\n\n", FILE_APPEND);
}


function intertrading_error_codes($code, $args = array()) {
  //$code 'OK' should never be passed to this function
  switch ($code) {
    //transaction
    case 1: return t("Transaction would exceed your system's max limits on server by @quantity", $args);
    case 2: return t("Transaction would exceed your system's min limits on server by @quantity", $args);
    case 3: return t("Transaction would exceed dest max limits on server by @quantity", $args);
    case 4: return t("Transaction would exceed dest min limits on server by @quantity", $args);
    case 5: return t('Problem saving transaction on server: @message', $args);
    case 6: return t('Unknown account & failed to create a new account on intertrading server');
    case 7: return t('Diagnostics from server: @message', $args);
    case 8: return t('Transaction field @fieldname is invalid: : @value', $args);
    //failure to authenticate or validate handshake with server
    case 11: return t('Missing config field: @fieldname', $args);
    case 12: return t('Field @fieldname should be @operator 0: @val', $args);
    case 13: return t('Field @fieldname contains invalid characters', $args);
    case 14: return t('Your Intertrading ratio (balance/volume) exceeds @num%: @balance / @volume', $args);
    case 15: return t("Type error in field '@fieldname'. Should be a @type.", $args);
    case 16: return t("Your exchange is exists, but is blocked on the intertrading network.");
    case 17: return t("Wrong key");
    case 18: return t("Not enough data to authenticate");
    //failure of remote client
    case 20: return t("Failed to connect with @server; returned: @message", $args);
    case 21: return t("Transaction would exceed remote client's max intertrading limits by @quantity", $args);
    case 22: return t("Transaction would exceed remote client's min intertrading limits by @quantity, $args");
    case 23: return t("Transaction would exceed remote client's min account limits by @quantity, $args");
    case 24: return t("Transaction would exceed remote client's max account limits by @quantity", $args);
    case 25: return t("Misc validation error on remote client: @message", $args);
    case 26: return t("server not found: @server", $args);
    case 27: return t("Server failed to connect to @client; http status @status", $args);
    case 28: return t("Unknown mail on @client: @mail", $args);
    default: return t('Unknown error code @code: @args', array('@code' => $code, '@args' => print_r($args, 1)));
  }
}

function urldecode_m(&$var, $key) {
  $var = urldecode($var);
}