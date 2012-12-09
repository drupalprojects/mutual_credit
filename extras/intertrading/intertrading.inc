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
  if (!empty($result->data)) {
    if ($data = drupal_json_decode($result->data)) {
      array_walk_recursive($data, 'urldecode_m');
      $return->code = array_shift($data);
      $return->args = $data;
      if ($result->code == 200) {//valid intertrading result
        //now handle intertrading errors
        if ($return->code == 'OK') {
          return $return;
        }
        else {
          $return->message = html_entity_decode(intertrading_error_codes($return->code, $return->args));
          watchdog('intertrading', 'Site @site returned error @message', array('@site' => $url,  '@message' => $return->message), WATCHDOG_NOTICE);
        }
      }
      else {//this could mean a 500 error or something
        watchdog('intertrading', 'Unexpected result from @url: @result', array('@url' => $url, '@result' => print_r($result, 1)), WATCHDOG_NOTICE);
        $return->message = t('Server reports: '). html_entity_decode($result->data);
        $return->code = 27;
      }
    }
    else {
      intertrading_error('retrieved data not in json format:'.json_last_error(), $url, $result->data, 'decoded:', drupal_json_decode($result->data));
      $return->message = 'retrieved data not in json format';
      $return->code = 27;
    }
  }
  //http status codes failure
  else {
    intertrading_error('no data retrieved', $url, $result);
    //this doesn't catch "php_network_getaddresses: getaddrinfo failed: Name or service not known" with code 0
    $return->args = array('@client' => $url, '@status' => $result->code);
    if (isset($result->error)) {
      $return->args['@error'] = strval($result->error);
    }
    $return->code  = 27;
    $return->message = html_entity_decode(intertrading_error_codes(27, $return->args));
    watchdog('intertrading', "@message", array('@message' => $return->message), WATCHDOG_ERROR);
  }
  return $return;
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
  foreach (array('payer', 'payee', 'quantity') as $fieldname) {
    if (empty($data[$fieldname])) {
      $error = array(8,  array('@value' => '', '@fieldname' => $fieldname));
    }
    else {
      $error = intertrading_xss_validate($data[$fieldname], $fieldname);
    }
  }
  return $error;
}

function intertrading_xss_validate($val, $fieldname) {
  $filtered = filter_xss($val, array());
  if($val != $filtered) {
    return array(13, array('@fieldname' => $val));
  }
}

//this is the definitive list of incoming fields.
function intertrading_handshake_fields() {
  //each element states its type, for validation, whether it is required, and an optional argument for the validator
  return array(
    'src_url' => array('url', TRUE),
    'mail' => array('mail', TRUE),
    'ticks' => array('float', TRUE, '>'),
    'divisions' => array('divisions', FALSE),
    'api' => array('api', TRUE),
    'name' => array('string', TRUE, NULL),
    'visibility' => array('toggle', FALSE), //defaults to off
    'logo' => array('string', FALSE), //some kind of validation would be nice
    'lat' => array('float', FALSE),
    'lon' => array('float', FALSE),
    'new_key' => array('key', FALSE),
    'traders' => array('integer', TRUE, '>='),
    'transactions' => array('integer', TRUE, '>='),
  );
}

function intertrading_report_fields() {
  return array(
    'volume' => array('float', TRUE, '>='),
    'deficit' => array('float', TRUE, '<='),
    'balance' => array('float', TRUE),
    'integrity' => array('toggle', TRUE, '>')
  );
}

function intertrading_error() {
  $args = func_get_args();
  //$call = array_shift($call);
//file_put_contents('intertrading_test.msg', implode("\n", $call)."\n\n", FILE_APPEND);
  //$loc = "Called from function <b>".$call['function']."</b> on line <b>". $call['line'] ."</b> in file <b>".$call['file'].'</b>';
  //array_unshift($args, $loc);
  foreach ($args as $arg) {
    $strings[] = print_r($arg, 1);
  }
  watchdog('intertrading error', '!message', array('!message' => '<pre>'.implode('<br />', $strings).'</pre>'), WATCHDOG_DEBUG);
  //file_put_contents('intertrading.msg', implode("\n", $strings)."\n\n", FILE_APPEND);
}


function intertrading_error_codes($code, $args = array()) {
  //$code 'OK' should never be passed to this function
  switch ($code) {
    //transaction
    case 4: return mcapi_limits_exception_parse(unserialize($args['@message']));
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
    case 17: return t("Wrong key: @key", $args);
    case 18: return t("Not enough data to authenticate");
    //failure of remote client
    case 20: return t("Failed to connect with @server; returned: @message", $args);
    case 22: return t("Transaction would exceed remote client's intertrading limits by @quantity", $args);
    case 24: return t("Transaction would exceed remote client's account limits by @quantity", $args);
    case 25: return t("Misc validation error on remote client: @message", $args);
    case 26: return t("client not found: @server", $args);
    case 27: return t("Failed to connect: @params", array('@params' => '<pre>'.print_r($args, 1).'</pre>'));
    case 28: return t("Unknown mail on @client: @mail", $args);
    default: return t('Unknown error code @code: @args', array('@code' => $code, '@args' => print_r($args, 1)));
  }
}

function urldecode_m(&$var, $key) {
  $var = urldecode($var);
}