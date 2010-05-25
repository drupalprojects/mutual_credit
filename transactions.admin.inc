<?php 
// $Id$
/*
 * @file
 * This file contains admin pages and also some function which only happen when a transaction is modified
 */

function cc_options() {
  $form['cforge_report'] = array(
    '#type' => 'checkbox',
    '#title' => t('Tell Community Forge about this community.'),
    '#default_value' => variable_get('cforge_report', TRUE),
    '#size' => 2,
    '#maxlength' => 3,
    '#weight' => -5,
    '#description' => t("Connect with a wider network by sharing a few key pieces of information with !cforge.", 
        array('!cforge' => l('Community Forge', 'http://communityforge.net', array('absolute' => TRUE)))).' '.
      t("e.g. url, contact details, number of active traders.") .' '.
      t("No personal information will be shared.")
  );
  $form['cc_description_min_words'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum number of words to describe a transaction'),
    '#default_value' => variable_get('cc_description_min_words', 4),
    '#size' => 2,
    '#maxlength' => 3,
    '#weight' => -4,
    '#description' => t("Classification can be improved if members take a few seconds to describe their transactions in more detail.") .' '.
      t("It will also make the transaction easier to categorise, and easier for the participants to remember. Suggested value is 4.")
  );
  $form['cc_balancing_account_num'] = array(
    '#type' => 'textfield',
    '#title' => t('Number of the balancing account'),
    '#default_value' => variable_get('cc_balancing_account_num', 1),
    '#size' => 2,
    '#maxlength' => 4,
    '#weight' => -4,
    '#description' => t("The balancing account is important, but not strictly neccessary. It is usually used as a central kitty, or bank."), 
  );
  
  $form['maintenance'] = array(
    '#type' => 'fieldset',
    '#title' => t('Maintenance'),
    '#weight' => -3,
    '#description' => t('In case the balances get out of sync with the transactions.') .' '. 
      t('Please use this and report an issue at !url', array('!url' => l('drupal.org', 'http://drupal.org/project/mutual_credit', array('absolute' => TRUE)))),
    '#collapsible' => FALSE,
  );
  $form['maintenance']['refresh'] = array(
    '#type' => 'submit',
    '#value' => t("Recalulate balances & stats"),
    '#submit' => array('cc_balances_refresh'),
  );
  
  $form['user_selection'] = array(
    '#type' => 'fieldset',
    '#title' => t('Selecting other users'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => -3,
  );
  $form['user_selection']['cc_select_user_orderby'] = array(
    '#type' => 'radios',
    '#title' => t('User dropdown order'),
    '#default_value' => variable_get('cc_select_user_orderby', 'uid'),
    '#description' => t("On the new transaction form, in what order should the members be listed?"),
    '#options' => array('uid' => t('User ID'), 'name' => t('Username'), 'themed_username' => t('themed username')),
  );
  $form['user_selection']['cc_select_user_show'] = array(
    '#type' => 'select',
    '#title' => t('User dropdown field(s)'),
    '#default_value' => variable_get('cc_select_user_show', 'name'),
    '#multiple' => TRUE,
    '#description' => t("On the new transaction form, what fields should be used to identify members in the dropdown?") .
      t("Neither field order nor the spacer character can be configured. Would be great if you choose a view for this, like a cck field."),
    //to add more options from the profile field, use hook_menu_alter in cc_custom module
    '#options' => array('uid' => t('User ID'), 'name' => t('Username'), 'mail' => t('Email'), 'themed_username' => t('themed username') ),
  );
  $form['user_selection']['cc_autocomplete_user_select_widget'] = array(
    '#type' => 'radios',
    '#title' => t('Widget for selecting users'),
    '#default_value' => variable_get('cc_autocomplete_user_select_widget', 'select'),
    '#options' => array(
      'select' => t('Dropdown box'),
      'textfield' => t('Autocomplete username'),
    ),
    '#description' => t("When you have more users than can fit in a dropdown box, you should probably use an autocomplete field instead."),
  );
  $form['visualisation'] = array(
    '#type' => 'fieldset',
    '#title' => t('Statistics & visualisation'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['visualisation']['cc_stats_periods'] = array(
    '#type' => 'textarea',
    '#title' => 'Periods for which stats are available',
    '#default_value' => implode("\n", variable_get('cc_stats_periods', array("1 month", "1 year"))),
    '#description' => t('On each line write a period using php !strtotime syntax, for which stats will be cached. ', array('!strtotime' => l('strtotime', 'http://php.net/strtotime'))) . 
      t('For each period there is a tab on the !stats page, and a block.', array('!stats' => l('stats', 'stats'))),
  );
  $form['visualisation']['cc_count_pending'] = array(
    '#type' => 'checkbox',
    '#title' => t('Count pending transactions'),
    '#description' => t('Should transactions which have not yet been confirmed be included in transaction listings and in stats?'),
    '#default_value' => variable_get('cc_count_pending', FALSE),
  );  
  $names = variable_get('cc_transaction_types', array());
  $form['transactions'] = array(
    '#type' => 'fieldset',
    '#title' => t('Names of transaction types'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => -2,
    '#help' => t('These are the names of the transaction types. Unnamed types will be unavailable. You must name at least one. ') .
        t('For each type there is a block, a menu link (coming soon) and a permission'),
  );
  $form['transactions']['transactions__outgoing_confirm'] = array(
    '#type' => 'textfield',
    '#title' => t('The name of an @direction flow completed @when', array('@direction' => t('outgoing'), '@when' => t('by the other party'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['outgoing_confirm'],
  );
  $form['transactions']['transactions__incoming_confirm'] = array(
    '#type' => 'textfield',
    '#title' => t('The name of an @direction flow completed @when', array('@direction' => t('incoming'), '@when' => t('by the other party'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['incoming_confirm'],
  );
  $form['transactions']['transactions__outgoing_direct'] = array(
    '#type' => 'textfield',
    '#title' => t('The name of an @direction flow completed @when', array('@direction' => t('outgoing'), '@when' => t('instantly'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['outgoing_direct'],
  );
  $form['transactions']['transactions__incoming_direct'] = array(
    '#type' => 'textfield',
    '#title' => t('The name of an @direction flow completed @when', array('@direction' => t('incoming'), '@when' => t('instantly'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['incoming_direct'],
  );
  $form['#submit'] = array('menu_rebuild', 'stats_refresh_all', 'refresh_trader_names');

  return system_settings_form($form);
}

function cc_options_validate(&$form, &$form_state) {
  if (intval($form_state['values']['cc_description_min_words']) < 1) {
    form_set_error('description_min_words', t('Minimum number of words must be a positive integer'));
  }
  if (!is_numeric($form_state['values']['cc_balancing_account_num'])) {
    form_set_error('cc_balancing_account_num', t('Balancing account must be an integer.'));
  }
  $periods=array();
  if (strlen($form_state['values']['cc_stats_periods'])) {
    foreach (explode("\n", $form_state['values']['cc_stats_periods']) as $period) {
      if (!is_integer(strtotime($period))) {
        form_set_error('cc_stats_periods', t("'@period' is not a valid strtotime string", array('@period' => $period)));
      }
      $periods[]= trim($period);
    }
  }
  $form_state['values']['cc_stats_periods'] = $periods;
  //we want to work on all the keyes which have __
  foreach ($form_state['values'] as $key => $value) {
    //the module names was preserved as a prefix in the field name
    if (strpos($key, '__')) {
      $parts = explode('__', $key);
      if (strlen($value)) {
        $nestedvalues[$parts[0]][$parts[1]] = $value;
      }
      unset($form_state['values'][$key]);
    }
  $form_state['values']['cc_transaction_types'] = $nestedvalues;
  }
}

//builds the form for configuring transactions.
function default_currency_config() {
  $currency= variable_get('cc_default_currency', NULL);
  $form = currency_form_fields($currency);
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Currency name'),
    '#default_value' => $currency->title,
    '#description' => t('Use the plural'),
    '#required' => TRUE,
    '#weight' => -10,
    '#size' => 10,
  );
  $form['#prefix'] = t("This default currency is made obselete if the cc_currencies module is installed.");
  $form['#validate'][] = 'currency_validate';// this must go before default_currency_config_validate
  $form['submit']=array (
    '#type' => 'submit',
    '#value' => t('Edit'),
    '#weight' => 15,
  );
  return $form;
}

function currency_form_fields($currency) {
  $form['#attributes'] = array('enctype' => "multipart/form-data");
  $form['division'] = array(
    '#type' => 'radios',
    '#title' => t('Currency subdivision'),
    '#default_value' => $currency->division,
    '#options' => array(
      'integer' => t('integer'),
      'sixtieths' => t('quarters (of an hour)'),
      'decimal' => t('hundredths (two decimal places)'),
    ),
    '#description' => t('Many LETS currencies are not divisible, while hours might be divisible into quarters.'),
  );
  $form['balance_limits'] = array(
    '#type' => 'fieldset',
    '#title' => t('Balance limits'),
    '#description' => t("Balance limits are calculated AFTER the offset.") .' '.
      t("Only users with 'Edit all transactions' permission can do trades which take users outside their balance limts") .' '.
      t("Those users can also edit individual balance limits on users' account pages"),
    '#weight' => 5,
  );
  $form['balance_limits']['max_balance'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum Balance'),
    '#default_value' => $currency->max_balance,
    '#size' => 4,
    '#maxlength' => 6,
    '#weight' => 0,
    '#description' => t("Transactions will be declined by the system if the payee balance would go above this number. Up to !num digits.", array('!num' => 6)) .' '.
    t("Leave blank for no limit (not reccomended)") .' '. 
    t('Maximum balance must be an integer larger than zero.'),
  );
  $form['balance_limits']['min_balance'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum Balance'),
    '#default_value' => $currency->min_balance,
    '#size' => 4,
    '#maxlength' => 6,
    '#weight' => 1,
    '#description' => t("The system will prevent transactions if the payer balance would go below this number. Up to !num digits.", array('!num' => 7)) . 
      t("Leave blank for no limit (not reccomended)") .' '. 
      t('Minimum balance must not be larger than 0'),
  );
    $form['balance_limits']['zero_offset'] = array(
    '#type' => 'textfield',
    '#title' => t('Offset balances'),
    '#default_value' => $currency->zero_offset,
    '#size' => 4,
    '#maxlength' => 6,
    '#weight' => 2,
    '#description' => t("Although each currency is a closed, zero-balance system, the baseline balance can be offset from 0.") .' '.
      t("This gives the impression that users are starting with a positive or negative balance.") .' '.
      t("Members start with x units 'free' but also need to restore their balance to x when they leave.") .' '.
      t("Instead of this trick, consider gifting new members from the central account, and recouping the debt through taxes"),
  );
  $form['visual'] = array(
    '#type' => 'fieldset',
    '#title' => t('Visuals'),
    '#description' => t("Displays of money can be further tweaked by overriding theme_money and/or with css class 'currency-0'"),
  );
  $form['visual']['icon_upload'] = array(
    '#type' => 'file',
    '#title' => t('Upload icon'),// array('!icon' => theme('1', $currency))),
    '#prefix' => '<img src="'. url($currency->icon) .'" />',
    '#description' => t("Should be between 12px square and 16px square, preferably with a transparent background"),
    '#weight' => -10,
  );
  $form['comparative_value'] = array(
    '#type' => 'textfield',
    '#title' => t('Comparative value'),
    '#default_value' => $currency->comparative_value,
    '#size' => 4,
    '#maxlength' => 6,
    '#weight' => 10,
    '#description' => t("If this currency is part of a larger trading network, it will have a value within that network (not yet operational)")
  );
  $form['visual']['color'] = array(
    '#type' => 'textfield',
    '#title' => t('A hex colour value'),
    '#default_value' => $currency->color,
    '#required' => TRUE,
    '#description' => t('6 hex digits'),
    '#weight' => 3,
    '#maxlength' => 6,
    '#size' => 6,
  );
return $form;
}


//save the default currency to a variable
function default_currency_config_submit($form, &$form_state) {
  $cc_default_currency = variable_get('cc_default_currency', (object)array());
  $cc_default_currency->nid = 0;
  $cc_default_currency->cid = 0;
  $cc_default_currency->title = $form_state['values']['title'];
  $cc_default_currency->max_balance = $form_state['values']['max_balance'];
  $cc_default_currency->min_balance = $form_state['values']['min_balance'];
  $cc_default_currency->comparative_value = $form_state['values']['comparative_value'];
  $cc_default_currency->zero_offset = intval($form_state['values']['zero_offset']);
  $cc_default_currency->division = $form_state['values']['division'];
  $cc_default_currency->color = $form_state['values']['color'];
  //need to test this
  if ($form_state['values']['icon']) {
    $cc_default_currency->icon = $form_state['values']['icon'];
  }
  variable_set('cc_default_currency', $cc_default_currency);
}

function transaction_quality_config() {
  $saved_array=variable_get('cc_transaction_qualities', array());
  foreach ($saved_array as $key => $word) {
    $default_string .= $key .'|'. $word ."\n";
  }
  $form['cc_transaction_qualities'] = array(
    '#title' => 'Possible ratings',
    '#type' => 'textarea',
    '#default_value' => $default_string,
    '#description' => t('Traders can earn a reputation when their customers rate them highly. Use this field to list the words used to describe the quality of transactions, from worst to best, one on each line.') .' '.
      t('Example:<br />-1|Bad<br />0|As expected<br />1|Great!<br />  Leave blank to disable this feature. Note that in automated transactions, 0 will be be used, so 0 should be a neutral value.') .' '.
      t('Beware that changing the number of rating options after transactions have been entered will distort the averages.') .' '.
      t('This feature could use a lot more work to integrate into views and stats')
  );
  $form['#redirect'] = 'admin/marketplace';
  return system_settings_form($form);
}

function transaction_quality_config_validate($form, &$form_state) {
  $qualities = array();
  if (strlen(trim($form_state['values']['cc_transaction_qualities']))) {
    $qualities = explode("\n", $form_state['values']['cc_transaction_qualities']);
    if (count($qualities) == 1) {
      form_set_error('cc_transaction_qualities', t('You must have more than one rating, or leave the field blank to disable the rating system'));
    }
    //trim each value
    foreach ($qualities as $line) {
      $val_word = explode('|', $line);
      $save[$val_word[0]] = trim($val_word[1]);
    }
  }
  $form_state['values']['cc_transaction_qualities']=$save;
}

function validate_limits($minmax, $required = FALSE) {
  $fieldnames = array_keys($minmax);
  $limits = array_values($minmax);
  foreach ($limits as $key => $value) {
    if (!is_numeric($value) && ($required || strlen($value))) {
      form_set_error($fieldnames[$key], t("This must be an integer: @value", array('@value' => $value)));
    }
  }
  if ($limits['0'] > 0) {
    form_set_error($fieldnames[0], t('Minimum balance must not be larger than 0'));
  }
  if ($limits['1'] < 0 || $limits['1'] < $limits['0']) {
    form_set_error($fieldnames[1], t('Maximum balance must be an integer larger than zero and minimum balance'));
  }
  
}

//options might equal 
function generate_transaction_node($title, $payer_uid, $payee_uid, $quantity, $options = array(), $cid=0) {
  global $user;
  if ($quantity <= 0) {
    drupal_set_message(t('@function was asked to create a transaction with negative value: @value', array('@function' => 'generate_transaction_node', '@value' => $quantity)));
    return;
  }
  $node = $options + array(
   //default options
    'state' => TRANSACTION_STATE_COMPLETED, 
    'transaction_type' => 'api unspecified', 
    'quality' => NULL, 
    'starter_uid' => $user->uid,
    'completer_uid' => $payer_uid,
    'uid' => $user->uid,
    'created' => time(),
   //other incoming arguments
    'cid' => $cid,
    'payee_uid' => $payee_uid,
    'payer_uid' => $payer_uid,
    'quantity' => $quantity,
    'title' => $title,
   //some basic node properties
    'type' => 'transaction',
    'status' => 0, //this adds protection against anonymous users seeing transactions
  );
  $node=(object)$node;
  //print_r($node);
  node_save($node);
  return $node;
}


/*
 * if even one currency has float values, we need to change the whole database, and back again
 */
function alter_quantity_field_types($type) {
  $newfield['type'] = $type;
  
  $newfield['description'] = "number of units transacted, ($type)";
  db_change_field($ret, 'cc_transactions', 'quantity', 'quantity', $newfield);
  
  $newfield['description'] = "sum of all completed transactions for this user in this currency, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'cleared_balance', 'cleared_balance', $newfield);
    
  $newfield['description'] = "sum of all pending transactions, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'pending_difference', 'pending_difference', $newfield);
    
  $newfield['description'] = "balance including pending transactions, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'pending_balance', 'pending_balance', $newfield);
    
  $newfield['description'] = "the sum of all this user's income in this currency, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'gross_income', 'gross_income', $newfield);
  
  $newfield['description'] = "the sum of all this user's expenditure in this currency, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'gross_expenditure', 'gross_expenditure', $newfield);
  $schema = drupal_get_schema('cc_transactions');
  if ($schema['fields']['quantity']['type'] != $type) {
    drupal_set_message(t("Drupal was unable to set your database field types to @type", array('@type' => $type)));
  }
  else {
    drupal_set_message(t("Quantitative fields in database is set to type '@type'", array('@type' => $type)));
  }
}

function cforge_report() {
  $data = array(
    'server' => $_SERVER["HTTP_HOST"],
    'site_name' => variable_get('site_name', 'site_name'),
    'user_count' => db_result(db_query("SELECT COUNT(*) FROM {users}")),
    'transactions_last_month' => db_result(db_query("SELECT COUNT(*) from {node} WHERE created > %d ", strtotime('-1 month'))),
  );
  if (!function_exists('http_build_query')) { 
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


//helper function for testing
function generate_transactions($howmany) {
  //get a list of all user ids and currency Ids
  $results1 = db_query("SELECT uid FROM {users} WHERE uid <> 1");
  $results2 = db_query("SELECT nid FROM {node} WHERE type = 'currency'");
  while ($result = db_fetch_object($results1)) {
    $all_users[] = $result->uid;
  }
  while ($result = db_fetch_object($results2)) {
    $all_currencies[] = $result->nid;
  }
  $usercount = count($all_users);
  $currencycount = count($all_currencies);
  $options = array(
    'type' => 'auto-generate',
  );
  while ($i < $howmany) {
    $i++;
    $options['created'] = time()-3600*24*rand(1, 60);
    generate_transaction_node('autogenerated transaction', $all_users[rand(0, $usercount-1)], $all_users[rand(0, $usercount-1)], rand(1, 50), $options, $all_currencies[rand(0, $currencycount-1)]);
  }
  cc_balances_refresh();
}


function cc_balances_refresh() {
  //get a list of all the users and all the currencies they have traded in 
  $currencies = currencies_load();
  $traders = cache_get('cc_trader_list');
  foreach (array_keys($currencies) as $cid) {
    foreach (array_keys($traders->data) as $uid) {
      recalculate_balances($uid, $cid);
    }
  }
  drupal_set_message(t('Flushed balance cache'));
  stats_refresh($cid);
}

//go through all the user's transactions and calculate balance,
//Could more of this be done by SQL?
function recalculate_balances($uid, $cid) {
  module_load_include('inc', 'transactions');
  $gross_income=0;
  $gross_expenditure=0;
  $outgoings=0;
  $pending_dif=0;
  $pending_balance=0;
  $transactions=NULL;
  $options =  array(
    'states' => array(TRANSACTION_STATE_COMPLETED, TRANSACTION_STATE_PENDING),
    'cid' => $cid,
   );
  //we don't get the running balance because we're going to iterate through everything anyway
  $transactions = _get_transactions_for_user($uid, $options);
  if (count($transactions)) {
    $count_pending = variable_get('cc_count_pending', FALSE);
    //so we work out out the balance, pending and gross_income again in more detail
    foreach ($transactions as $t) {
      if ($t->state == TRANSACTION_STATE_COMPLETED) { //completed transactions
        if ($t->payer_uid == $uid) {
          $gross_expenditure += $t->quantity;
        }
        else {
          $qualities[] = $t->quality;
          $gross_income += $t->quantity;
        }
      }
      elseif ($t->state == TRANSACTION_STATE_PENDING) { //unpublished transactions are pending
        if ($t->payer_uid == $uid) {
          $pending_dif -= $t->quantity;
          if ($count_pending) {
            $gross_expenditure += $t->quantity;
          }
        }
        else {
          $pending_dif += $t->quantity;
          if ($count_pending) {
            $gross_income += $t->quantity;
          }
        }
      }
    }
  }
  $cleared_balance = $gross_income-$gross_expenditure + $currency->zero_offset;
  $pending_balance = $cleared_balance - $pending_dif;
  $quality_mean = count($qualities) ? array_sum($qualities) / count($qualities) : 0;

  db_query(
    "REPLACE INTO {cc_balance_cache}
      SET uid = %d,
      cid = %d,
      cleared_balance = %f,
      pending_balance = %f,
      pending_difference = %f,
      gross_income = %f,
      gross_expenditure = %f,
      quality_mean = %f",
    array($uid, $cid, $cleared_balance, $pending_balance, $pending_dif, $gross_income, $gross_expenditure, $quality_mean)
  );
}

 /*
  * Stats generator
  * caches an array of common stats for one currency
  * $timestring is a positive number of days or months e.g. '3 months'
  * 
  */
function stats_refresh($cid = 0) {
  $periods = variable_get('cc_stats_periods', array("1 month", "1 year"));
  foreach ($periods as $period) {
    $period = trim($period);
    //this excludes transactions with the balancing account
    //Anything involving a member count includes only the active members
    $stats = array();
    $earned = array();
    $spent = array();
    $trades = array();
    $volume = array();
    $states = array(TRANSACTION_STATE_COMPLETED);
    if (variable_get('cc_count_pending', FALSE)) {
      $states[] = TRANSACTION_STATE_PENDING;
    }
    $result = db_query("SELECT * FROM {cc_transactions} AS t 
      LEFT JOIN {node} AS n ON t.nid = n.nid
      WHERE n.created > %d
      AND t.cid = %d
      AND state in (%d)
      AND t.payer_uid <> %d AND t.payee_uid <> %d", 
      array(
        ':since' => strtotime('-'. $period),
        ':cid' => $cid,
        ':states' => implode(',', $states),
        ':payer_uid' => variable_get('cc_balancing_account_num', 1),
        ':payee_uid' => variable_get('cc_balancing_account_num', 1)
      )
    );
      
    //iterate through the transactions, adding stuff up
    $count=0;
    while ($t = db_fetch_object($result)) {
      $earned[$t->payee_uid] = $earned[$t->payee_uid] + $t->quantity;
      $spent[$t->payer_uid] = $spent[$t->payer_uid] + $t->quantity;
      $trades[$t->payee_uid] ++;
      $trades[$t->payer_uid] ++;
      $count ++;
    }
    if (!$count) continue;
    $active_members = count($trades);
    foreach ($trades as $uid => $val) {
      $volume[$uid] = $earned[$uid] + $spent[$uid];
    }
    asort($earned);
    asort($spent);
    asort($volume);
    asort($trades);
    $stats['highest_incomes'] = array_reverse($earned, TRUE);
    $stats['highest_expenditures'] = array_reverse($spent, TRUE);
    $stats['highest_volumes'] = array_reverse($volume, TRUE);
    $stats['trades_per_user'] = array_reverse($trades, TRUE);
    $stats['misc']['active_members'] = $active_members;
    $stats['misc']['total_volume'] = array_sum($stats['highest_volumes'])/2;
    $stats['misc']['mean_volume_active'] = 2*intval(array_sum($stats['highest_volumes'])/$active_members);
    $stats['misc']['transaction_count'] = $count;
    //we might not need this one, since it is obtained by simple division of the above
    $stats['misc']['mean_transaction_count_active'] = $count/floatval($active_members);
    foreach ($stats as $key => $stat) {
      cache_set($cid .'-'. str_replace(' ', '_', $period) .'-'. $key, $stat);
    }
  }
}
/*
  * TO CALL THIS FROM ANYWHERE, COPY THIS
  * module_load_include('admin.inc', 'transactions');
  * stats_refresh_all();
  */
function stats_refresh_all() {
  foreach (currencies_load() as $currency) {
    stats_refresh($currency->cid);
  }
}

function refresh_trader_names() {
  if ($roles = user_roles(TRUE, 'use local currency')) {
    unset($roles[DRUPAL_AUTHENTICATED_RID]);
    $where = array('status = 1');
    if (count($roles)) {
      $where[] = 'rid in ('. implode(', ', array_keys($roles)) .')';
    }
    $results = db_query("SELECT u.uid 
      FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid
      WHERE %s
      ORDER BY `u`.`". variable_get('cc_select_user_orderby', 'uid') ."` ASC",
        array(':where' => implode(' AND ', $where))
    );
    
    $account = array();
    while ($account = user_load(db_result($results))) {
      foreach (variable_get('cc_select_user_show', array('name'))  as $field) {
        switch ($field) {
          case 'name':
          case 'mail':
          case 'uid':
            $fields[$field] = $account->$field;
            break;
          case 'themed_username':
            $fields[$field] = strip_tags(theme('username', $account));
        }
      }
      $names[$account->uid]= implode(' | ', $fields);
    }
    cache_set('cc_trader_list', $names);
  }
  else {
    drupal_set_message(
      t(
        'No roles have permission to @trade. Go to !link', 
        array(
          '@trade' => t('use local currency'),
          '!link' => l('admin/user/permissions', 'admin/user/permissions')
        ),
      'error'
      )
    );
  }
}