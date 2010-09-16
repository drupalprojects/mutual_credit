<?php
// $Id$

/**
 * @file
 * This file contains all functions to view exchange content
 */


function mcapi_form_currency(&$node) {
  //TODO
  //add a maximum transaction size per currency
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Currency name'),
    '#default_value' => $node->title,
    '#description' => t('Use the plural'),
    '#required' => TRUE,
    '#size' => 10,
  );
  $form['body'] = array(
    '#type' => 'textarea',
    '#title' => t('Rationale'),
    '#default_value' => $node->body,
    '#description' => t('What are the credentials of this currency? Richard Douthewaite poses these questions:') . theme('item_list', array(
      t('Who issues the money?'),
      t('Why do they issue it?'),
      t('Where is the money created?'),
      t('What gives the money its value?'),
      t('How is the money created?'),
      t('When is the money created?'),
    ), NULL, 'ol'),
    '#required' => TRUE,
    '#size' => 10,
  );
  $form['balance_limits'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default balance limits'),
    '#description' => t("Up to !num digits. The system will prevent exchanges if the either participant's balance would go outside these numbers.", array('!num' => 6)) . ' '.
      t("Users with 'edit all exchanges' permission can overridde these limits on the users' account pages, and bypass these limits in any exchanges they edit."),
    '#prefix' => '<style>#edit-min-wrapper, #edit-max-wrapper{width:49%;float:left;}</style>', //helps with garland theme at least
    '#element_validate' => array('mc_validate_limits')
  );
  $def_max = isset($node->max) ? $node->max : 100;
  $form['balance_limits']['min'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum Balance'),
    '#default_value' => intval($node->min),
    '#size' => 7,
    '#maxlength' => 7,
    '#description' => t('Must not be larger than 0'),
    '#attributes' => array('style' => 'width:inherit;') //garland says all form textfields should be 100%
  );
  $form['balance_limits']['max'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum Balance'),
    '#default_value' => $def_max,
    '#size' => 6,
    '#maxlength' => 6,
    '#weight' => 1,
    '#description' => t('Must be a positive integer.'),
    '#attributes' => array('style' => 'width:inherit;'),
    '#suffix' =>t("Leave blank for no limit (not reccomended).")
  );

  $form['visual'] = array(
    '#type' => 'fieldset',
    '#title' => t('Appearance'),
    '#description' => t("Displays of money can be further tweaked by overriding theme_money and/or with css class 'currency-0'"),
  );
  $form['#attributes'] = array('enctype' => "multipart/form-data");
  $form['visual']['icon'] = array(
    '#type' => 'file',
    '#title' => t('Upload icon'),// array('!icon' => theme('1', $currency))),
    '#prefix' => t('Current icon') .': ' . theme('image', $node->icon),
    '#description' => t("Probably will be about the same size as the page text, preferably with a transparent background.") .' '.
      t("Drupal them will normally resize at client side in HTML."),
    '#element_validate' => array('mc_validate_icon_upload'),
    '#size' => 25,//the default, 60, ruins the 2 col theming
  );
  $form['visual']['color'] = array(
    '#type' => 'textfield',
    '#title' => t('A hex colour value'),
    '#default_value' => $node->data['color'] ? $node->data['color'] : '000000',
    '#description' => t('6 hex digits'),
    '#maxlength' => 6,
    '#size' => 6,
    '#field_prefix' => '#'
  );

  $form['sub_divisions'] = array(
    '#type' => 'fieldset',
    '#title' => t('Fractions'),
  );
  $form['sub_divisions']['sub'] = array(
    '#type' => 'radios',
    '#default_value' => intval($node->sub),
    '#options' => array(
      '0' => t('1 unit is the smallest possible exchange'),
      '1' => t('Enable fractions').'...'
    )
  );
  $form['sub_divisions']['divisions'] = array(
    '#type' => 'textarea',
    '#title' => t('Fractions visible in form'),
    '#description' => '<p>'. t("Only applies when 'database storage' is set to 'fractions'.") .' '.
      t('Leave blank to use hundredths. To use a dropdown, such as for quarters of an hour, write a "hundredths value|visible text" on each line. E.g. ') .'</p>' .
      '<pre>0|exactly<br />50|half an hour',
    '#default_value' => mc_convert_array_settings($node->data['divisions']),
    '#element_validate' => array('mc_validate_settings_array', 'mc_validate_divisions'),
    '#rows' => 4,
  );
  $form['value'] = array(
    '#type' => 'textfield',
    '#title' => t('Comparative value'),
    '#default_value' => $node->value,
    '#size' => 4,
    '#maxlength' => 6,
    '#description' => t("The value of the currency in relation to other currencies in the same trading network (not yet operational)")
  );

  $form['ratings_area'] = array(//this is just cosmetic
    '#type' => 'fieldset',
    '#title' => t('Rating range'),
    '#description' => t("Payees gain reputation when their payers rate them highly.") .' '.
      t("This feature needs more work to integrate into views and stats, when users requre it.")
  );
  $form['ratings_area']['ratings'] = array(
    '#type' => 'textarea',
    '#default_value' => mc_convert_array_settings($node->data['ratings']),
    '#description' => t("Leave blank to disable this feature.") .' '.
      t("Use this field to list the words or expressions used to rate transactions, one on each line.") .' '.
      t("Use 0 for 'neutral'. E.g.").'<pre>-1|Bad<br />0|As expected<br />1|Great!</pre>' .
      t("Beware that changing the number of rating options after transactions have been entered will distort the averages."),
    '#element_validate' => array('mc_validate_settings_array')
  );
  $form['#redirect'] = 'admin/mc/currencies';
  return $form;
}



function theme_currency_node_form($form) {
  $output = "\n<div class=\"node-form currency\">\n";
  $output .= "\n<div style = \"width:49%;float:left;\">";
  $output .= drupal_render($form['title']);
  $output .= drupal_render($form['body']);
  $output .= drupal_render($form['value']);
  $output .= drupal_render($form['balance_limits']);
  //assuming webforms module is enabled - other modules will have to re-theme, for now
  $output .= drupal_render($form['exchange_subtypes']);
  $output .="</div>\n<div style = \"width:49%;float:right;\">";
  $output .= drupal_render($form['visual']);
  $output .= drupal_render($form['sub_divisions']);
  $output .= drupal_render($form['ratings_area']);
  $output .= "</div>";

  // Everything else gets rendered here, and is displayed before the admin form
  // field and the submit buttons.
  $output .= "  <div class=\"standard\">\n";
  $output .= drupal_render($form);
  $output .= "  </div>\n";

  $output.= drupal_render($form['buttons']);
  $output .= "</div>\n";

  return $output;
}


function mc_preprocess_exchange($node) {
  //created is only blank if the exchange isn't actually created
  if(!isset($node->created)) {
    $node->created = time();
    $props['title_link'] = $node->title;
  }
  else {
    $props['title_link'] = l($node->title, 'node/'. $node->nid);
  }
  $props['submitted'] = format_date($node->created, 'small');
  $props['payer'] = theme('username', user_load($node->payer_uid));
  $props['payee'] = theme('username', user_load($node->payee_uid));
  $props['amount'] = theme('money', $node->quantity, $node->cid);
  $props['rating'] = isset($node->rating) ? $node->rating : NULL;

  $props['classes'] = array();
  if (count($qualities = variable_get('cc_exchange_qualities', array()))) {
    $props['classes'][] = 'rating-'. $node->rating;
  }
  return $props;
}


//returns true if the user has permission to trade or has ever traded
//used for determining whether to display trading data for a user.
function trading_history($account) {
  if (user_access('exchange', $account) || count($account->balances)) {
    return TRUE;
  }
}


function mc_options() {
  $types = node_get_types();
  if (isset($type['exchange'])) {
    if (!db_result(db_query("SELECT nid from {exchanges}"))) {
      $form = array(
        'upgrade' => array(
          '#type' => 'submit',
          '#value' => t("Migrate data from Complementary Currencies v1 to v2"),
        )
      );
    }
  }

  $form['mc_count_pending'] = array(
    '#type' => 'checkbox',
    '#title' => t('Count pending exchanges'),
    '#description' => t('Should exchanges which have not yet been confirmed be included in the main balance calculation and in lists?'),
    '#default_value' => variable_get('mc_count_pending', TRUE),
  );

  $form['maintenance'] = array(
    '#type' => 'fieldset',
    '#title' => t('Maintenance'),
    '#description' => t('In case the balances get out of sync with the exchanges.') .' '.
      t('Please use this and report an issue at !url',
        array('!url' => l('drupal.org', 'http://drupal.org/project/mutual_credit', array('absolute' => TRUE)))
      ),
  );
  $form['maintenance']['refresh'] = array(
    '#type' => 'submit',
    '#value' => t("Recalulate balances & stats"),
    '#submit' => array('mc_balances_refresh'),
  );
  return system_settings_form($form);
}

//this could almost have been done with a view, but as this is the API module, we're saving a dependency
function currency_list() {
  $currencies = currencies_load();
  $headers = array(t('Symbol'), t('Name'), t('Unit-type'), t('usage'));
  $output = '';
  foreach ($currencies as $currency) {
    $row['icon'] = theme('image', $currency->icon);
    $row['name'] = l($currency->title, 'node/'.$currency->nid);
    $row['type'] = $currency->sub ? 'float' : 'integer';
    $row['usage'] = db_result(db_query("SELECT count(nid) FROM {mc_exchanges} WHERE cid = %d", array(':cid' => $currency->nid)));
    if (user_access('configure economy')) {
      $row['edit'] = l('edit', 'node/'. $currency->nid . '/edit');
    }
    $rows[] = $row;
  }
  //add an indicator showing what type the database is set to
  $rows[] = array(0 => '', 1 => '', 2 => '<strong>'. t('Database field type') .'</strong>', 3 => '', 4 => '');
  module_load_include('install', 'mcapi');
  $rows[] = array(0 => '', 1 => '', 2 => mcapi_value_type_existing(), 3 => '', 4 => '');
  $output .= theme('table', $headers, $rows);

  if (user_access('declare currency')) {
    $output .= l(t('Declare new currency'), 'node/add/currency');
  }
  return $output;
}

function mc_options_validate(&$form, &$form_state) {
  if($form['upgrade']) return;
  if (!is_numeric($form_state['values']['mc_balancing_account_num'])) {
    form_set_error('mc_balancing_account_num', t('Balancing account must be an integer.'));
  }
}
function mc_options_submit($form, $form_state) {
  //this function is in the install file in order to keep the volume of code down here.
  module_load_install('mcapi');
  mc_upgrade();
}


//return the max and min limits, by overriding currency limits with personal ones
//if $cid is not supplied an array of currencies is returned.
function user_limits($account, $cid = 0){
  foreach (currencies_load() as $currency) {
    $defaults = array(
      'max' => $currency->max,
      'min' => $currency->min
    );
    if (isset($account->limits[$currency->nid])) {
      $limits[$currency->nid] = (array)$account->limits[$currency->nid] + $defaults;
    }
    else {
      $limits[$currency->nid] = $defaults;
    }
  }
  if ($cid) return $limits[$cid];
  else return $limits;
}

function mc_admin_menu_block_page() {
  module_load_include('admin.inc', 'system');
  return drupal_get_form('cforge_report_form') .
    system_admin_menu_block_page();
}

function cforge_report_form() {
  return system_settings_form(array(
    'cforge_report' => array(
      '#type' => 'radios',
      '#title' => t('At the very least, please report your site stats to Community Forge '),
      '#default_value' => variable_get('cforge_report', TRUE),
      '#options' => array(
        0 => t('We do not wish to be known'),
        1 => t('Report our site data, but keep it private'),
        2 => t('Please make a directory, and put this site in it!')
      ),
      '#weight' => -5,
      '#description' => t("Be part of a wider network of local currency comunities by sharing a few key pieces of information with !cforge.",
          array('!cforge' => l('Community Forge', 'http://communityforge.net', array('absolute' => TRUE)))).' '.
        t("e.g. url, number of active traders.") .' '.
        t("Community Forge staff live from donations and these stats help motivate us and can be used to promote our work. They seek your trust and will not abuse this information")
    )
  ));
}

function cforge_report() {
  $traders = get_trader_list();
  $data = array(
    'server' => $_SERVER["HTTP_HOST"],
    'site_name' => variable_get('site_name', 'site_name'),
    'site_mail' => variable_get('site_mail', 'site_mail'),
    'user_count' => count($traders),
    'version' => db_result(db_query("SELECT schema_version from {system} WHERE name = 'mutual_credit'")),
    'transactions_last_month' => db_result(db_query("SELECT COUNT(*) from {node} WHERE created > %d and type = 'transaction'", strtotime('-1 month'))),
    'public' => variable_get('cforge_report', 1) -1,
  );

  if (!function_exists('http_build_query')) {//this is a php 5 function
      function http_build_query($data, $prefix='', $sep='', $key='') {
          $ret = array();
          foreach ((array)$data as $k => $v) {
              if (is_int($k) && $prefix != NULL) {
                  $k = urlencode($prefix . $k);
              }
              if ((!empty($key)) || ($key === 0))  $k = $key .'['. urlencode($k) .']';
              if (is_array($v) || is_object($v)) {
                  array_push($ret, http_build_query($v, '', $sep, $k));
              }
              else {
                  array_push($ret, $k .'='. urlencode($v));
              }
          }
          if (empty($sep)) $sep = ini_get('arg_separator.output');
          return implode($sep, $ret);
      }// http_build_query
  }
  $url = 'http://www.communityforge.net/registration.php?'. http_build_query($data, '', '&');
  $reply = drupal_http_request($url);
  drupal_set_message($reply->data);
}



function mc_balances_refresh() {
  db_query("TRUNCATE table {mc_cache}");
  //get a list of all the users and all the currencies they have traded in
  $currencies = currencies_load();
  $traders = cache_get('mc_trader_list');
  foreach (array_keys($currencies) as $cid) {
    foreach (array_keys($traders->data) as $uid) {
      //a more efficient function could be written for well established economies,
      //extracting all exchanges in one query
      _rebuild_exchange_history($uid, $cid);
    }
  }
  drupal_set_message(t('Flushed balance cache'));
  //need to tell the stats module to rebuild.
}

//refreshes a cache of all the exchanges, calculates the running balances and other totals
//this is an unusual kind of cache which should never be empty, because some functions access the cache table directly
//for this reason this function REPLACES rows.
function _rebuild_exchange_history($uid, $cid) {
  $fields = array('n.uid', 'n.title', 'n.nid', 't.payer_uid', 't.payee_uid', 't.quantity', 't.rating', 't.state', 'n.created', 't.rating', 't.exchange_type');

  $filters[]  = '('. " t.state = " . EXCHANGE_STATE_FINISHED ." OR t.state = " . EXCHANGE_STATE_PENDING .' )';
  $filters[] = " t.cid = $cid ";
  $filters[] = "(payer_uid = $uid OR payee_uid = $uid)";
  
  $query = "SELECT ". implode(', ', $fields) ."
    FROM {node} n
    LEFT JOIN {mc_exchanges} t ON t.nid = n.nid
    WHERE n.type = 'exchange'
    AND %s
    ORDER BY n.created ASC";
  $args = array(implode(' AND ', $filters));
  $results = db_query($query, $args);

  //now iterate through the exchanges making running balances
  $exchanges = array();
  $cleared_balance = 0;
  $pending_dif = 0;
  $gross_out = 0;
  $gross_in = 0;
  while ($exchange = db_fetch_object($results)) {
    //add to or subtract from the running balances
    if ($exchange->payer_uid == $uid) {
      if ($exchange->state == EXCHANGE_STATE_FINISHED) {
        $cleared_balance -=  $exchange->quantity;
      }
      else {
        $pending_dif -= $exchange->quantity;
      }
      $gross_out += $exchange->quantity;
    }
    else {
      if ($exchange->state == EXCHANGE_STATE_FINISHED) {
        $cleared_balance +=  $exchange->quantity;
      }
      else {
        $pending_dif -= $exchange->quantity;
      }
      $gross_in += $exchange->quantity;
    }
    $exchange->balance = $cleared_balance;
    if (variable_get('mc_count_pending', TRUE)) $exchange->balance += $pending_dif;
    $ratings[] = $exchange->rating;

    //add the exchange to the list if requred
    if ($exchange->state == EXCHANGE_STATE_FINISHED ||variable_get('count_pending', TRUE)) {
      $exchanges[$exchange->nid] = $exchange;
    }
  }
  $rating = array_sum($ratings) / count($ratings);

  db_query("REPLACE INTO {mc_cache} (uid, cid, cleared_balance, pending_dif, gross_in, gross_out, rating, exchanges)
    VALUES (%d, %d, %f, %f, %f, %f, %f, %b)",
    array($uid, $cid, $cleared_balance, $pending_dif, $gross_out, $gross_in, $rating, serialize($exchanges))
  );
}

/*
  * TO CALL THIS FROM ANYWHERE, COPY THIS
  * module_load_include('admin.inc', 'exchanges');
  * stats_refresh_all();
  */
function stats_refresh_all() {
  foreach (currencies_load() as $currency) {
    stats_refresh($currency->cid);
  }
}

//expects the settings array to be already validated, and just converts.
function mc_convert_settings_array($string) {
  $array = array();
  $lines = array_filter(explode("\n", $string));
  if (!count($lines)) return;
  foreach($lines as $line) {
    $vals = explode('|', $line);
    $array[$vals[0]] = trim($vals[1]);
  }
  return $array;
}

function mc_convert_array_settings($settings = array()){
  if (!count($settings)) return;
  while (list($key,$val) = each($settings)) {
    $lines[] = $key . '|'. $val;
  }
  return implode("\n", $lines);
}

function theme_mc_rating($rating, $cid) {
  if (isset($rating)) {
    $currency = node_load($cid);
    return $currency->data['ratings'][$rating];
  }
}

function theme_exchange_summary($node) {
  $submitted = isset($node->created) ? $node->created : time();
  $replacements = array(
    '!amount' => theme('money', $node->quantity, $node->cid),
    '@title' => isset($node->nid) ? l($node->title, 'node/'.$node->nid.'/title') : $node->title,
    '!payer' => theme('username', user_load($node->payer_uid)),
    '!payee' => theme('username', user_load($node->payee_uid)),
    '@submitted' => format_date($submitted, 'small'),
  );
  switch($node->state) {
    case EXCHANGE_STATE_PENDING:
      return t("On @submitted, !payer will pay !payee !amount for '@title'", $replacements);
    case EXCHANGE_STATE_FINISHED:
      return t("On @submitted, !payer gave !payee !amount for '@title'", $replacements);
    case EXCHANGE_STATE_DELETED:
      return t("On @submitted, !payer did not give !payee !amount for '@title'. (DELETED)'", $replacements);
  }
}
