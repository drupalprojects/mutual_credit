<?php 


function cc_options() {
  $form['cforge_report'] = array(
    '#type' => 'checkbox',
    '#title' => t('Tell Community Forge about this community.'),
    '#default_value' => variable_get('cforge_report', TRUE),
    '#size' => 2,
    '#maxlength' => 3,
    '#weight' => -5,
    '#description' => t("Become part of a wider community by allowing a coordinating to know a few things about this site. Such as url, contact details, number of active traders. No personal information will be shared.")
  );
  $form['cc_description_min_words'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum number of words to describe a transaction'),
    '#default_value' => variable_get('cc_description_min_words', 4),
    '#size' => 2,
    '#maxlength' => 3,
    '#weight' => -4,
    '#description' => t("This site could be better if you can get members to take a few seconds more to describe the transaction in more detail. It will also make the transaction easier to categorise, and easier for the participants to remember. Suggested value is 4.")
  );
  $form['cc_balancing_account_num'] = array (
    '#type' => 'textfield',
    '#title' => t('Number of the balancing account'),
    '#default_value' => variable_get('cc_balancing_account_num', 1),
    '#size' => 2,
    '#maxlength' => 4,
    '#weight' => -4,
    '#description' => t("The balancing account is important, but not strictly neccessary. It is usually used as a central kitty, or bank."), 
  );
  $form['cc_transaction_list'] = array (
    '#type' => 'radios',
    '#title' => t('Preferred transaction view'),
    '#default_value' => variable_get('cc_transaction_list', 1),
    '#weight' => -4,
    '#options' => array (
      'statement' => t('A table of all transactions Full access control; cannot be click-sorted; uses drupal pager; configure using THEME_statement()'),
      'ins_and_outs' => t('Seperate tables of incoming and outgoing transactions. Cruder access control; configure using views module.'),
    ),
    '#description' => t("Transaction monthly displays can be viewed in either of two formats. Both can be adjusted by theming."), 
  );
  //warning of calendar module dependency
  if (!(module_exists('calendar'))) {
    $form['cc_transaction_list']['#options']['ins_and_outs'] .= ' <font color="red">' . t("Pager requires cck, date & calendar modules") . '</font>';
  }
  
  $form['maintenance'] = array(
    '#type' => 'fieldset',
    '#title' => t('Maintenance'),
    '#weight' => -3,
    '#description' => t('In case the balances get out of sync with the transactions.') . ' ' . 
      t('Please use this and report an issue at !url', array('!url' => l('drupal.org', 'http://drupal.org/project/marketplace', array('absolute' => TRUE)))),
    '#collapsible' => FALSE,
  );
  $form['maintenance']['refresh'] = array (
    '#type' => 'submit',
    '#value' => t('Flush balances cache'),
    '#submit' => array('cc_balances_refresh'),
  );
  
  $form['user_selection'] = array(
    '#type' => 'fieldset',
    '#title' => t('Selecting other users'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => -3,
  );
  $form['user_selection']['cc_select_user_orderby'] = array (
    '#type' => 'radios',
    '#title' => t('User dropdown order'),
    '#default_value' => variable_get('cc_select_user_orderby', 'uid'),
    '#description' => t("On the new transaction form, in what order should the members be listed?"),
    '#options' => array('uid' => t('User ID'), 'name' => t('Username') ),
  );
  $form['user_selection']['cc_select_user_show'] = array (
    '#type' => 'select',
    '#title' => t('User dropdown field(s)'),
    '#default_value' => variable_get('cc_select_user_show', 'name'),
    '#multiple' => TRUE,
    '#description' => t("On the new transaction form, what fields should be used to identify members in the dropdown. Field order nor the spacer character can be configured. Would be great if you choose a view for this, like a cck field."),
    //to add more options from the profile field, use hook_menu_alter in cc_custom module
    '#options' => array('uid' => t('User ID'), 'name' => t('Username'), 'mail' => t('Email') ),
  );
  $form['user_selection']['cc_autocomplete_user_select_threshold'] = array(
    '#type' => 'textfield',
    '#title' => t('Threshold at which to use autocomplete widget on the user selection'),
    '#default_value' => variable_get('cc_autocomplete_user_select_threshold', 140),
    '#description' => t("When you have more users than can fit in a dropdown box, you should probably use an autocomplete field instead."),
  );
  
  $names = variable_get ('cc_transaction_types', array());
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
    '#title' => t('The name of an @direction flow completed @when', array('@direction'=>t('outgoing'), '@when'=>t('by the other party'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['outgoing_confirm'],
  );
  $form['transactions']['transactions__incoming_confirm'] = array(
    '#type' => 'textfield',
    '#title' => t('The name of an @direction flow completed @when', array('@direction'=>t('incoming'), '@when'=>t('by the other party'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['incoming_confirm'],
  );
  $form['transactions']['transactions__outgoing_direct'] = array(
    '#type' => 'textfield',
    '#title' => t('The name of an @direction flow completed @when', array('@direction'=>t('outgoing'), '@when'=>t('instantly'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['outgoing_direct'],
  );
  $form['transactions']['transactions__incoming_direct'] = array(
    '#type' => 'textfield',
    '#title' => t('The name of an @direction flow completed @when', array('@direction'=>t('incoming'), '@when'=>t('instantly'))),
    '#size' => 15,
    '#default_value' => $names['transactions']['incoming_direct'],
  );

  return system_settings_form($form);
}

function cc_options_validate(&$form, &$form_state) {
  if (intval($form_state['values']['cc_description_min_words']) < 1) {
    form_set_error('description_min_words', t('Minimum number of words must be a positive integer'));
  }
  if (!is_numeric($form_state['values']['cc_balancing_account_num'])) {
    form_set_error('cc_balancing_account_num', t('Balancing account must be an integer.'));
  }
  
  if (!is_numeric($form_state['values']['cc_autocomplete_user_select_threshold'])) {
    form_set_error('cc_autocomplete_user_select_threshold', t("This must be an integer: @value", array('@value' => $form_state['values']['cc_autocomplete_user_select_threshold'])));
  } else {
    $form_state['values']['cc_autocomplete_user_select_threshold'] = abs(intval($form_state['values']['cc_autocomplete_user_select_threshold']));
  }
  //we want to work on all the keyes which have __
  foreach($form_state['values'] as $key => $value){
    //the module names was preserved as a prefix in the field name
    if (strpos($key, '__')){
      $parts = explode('__', $key);
      if (strlen($value)){
        $nestedvalues[$parts[0]][$parts[1]] = $value;
      }
      unset ($form_state['values'][$key]);
    }
  $form_state['values']['cc_transaction_types'] = $nestedvalues;
  }
}

//builds the form for configuring transactions.
function default_currency_config(){
  $currency= variable_get('cc_default_currency', NULL);
  $form = currency_form_fields($currency);
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Currency name'),
    '#default_value' => $currency->title,
    '#description' => t('Use the plural'),
    '#required' => TRUE,
    '#weight' =>-10,
    '#size' => 10,
  );
  $form['#prefix'] = t("If there is more than one currency on the system, this currency will never be seen or used. the marketplace module does not use a default currency name, but you can use theme_money and/or the stylesheet, class 'currency-0'");
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
      'quarters' => t('quarters (of an hour)'),
      'decimal' => t('hundredths (two decimal places)'),
    ),
    '#description' => t('Many LETS currencies are not divisible, while hours might be divisible into quarters.'),
  );
  $form['max_balance'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum Balance'),
    '#default_value' => $currency->max_balance,
    '#size' => 4,
    '#maxlength' => 6,
    '#description' => t("Transactions will be declined by the system if the payee balance would go above this number. Up to !num digits.", array('!num'=>6)) . ' ' .
    t("Leave blank for no limit (not reccomended)") . ' ' . 
    t('Maximum balance must be an integer larger than zero.'),
  );
  $form['min_balance'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum Balance'),
    '#default_value' => $currency->min_balance,
    '#size' => 4,
    '#maxlength' => 6,
    '#description' => t("The system will prevent transactions if the payer balance would go below this number. Up to !num digits.", array('!num'=>7)) . 
    t("Leave blank for no limit (not reccomended)") . ' ' . 
    t('Minimum balance must not be larger than 0'),
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
    $form['zero_offset'] = array(
    '#type' => 'textfield',
    '#title' => t('Offset all balances by'),
    '#default_value' => $currency->zero_offset,
    '#size' => 4,
    '#maxlength' => 6,
    '#description' => t("Although each currency is a closed, zero-balance system, the baseline balance can be offset from 0. This could give the impression that users are starting with a positive or negative balance. Members start with x units 'free' but also need to restore their balance to x when they leave. Instead of this consider gifting new members fromt the central account, and storing all debt in the central account"),
  );
  $form['icon_upload'] = array(
    '#type' => 'file',
    '#title' => t('Upload currency icon'),// array('!icon' => theme('1', $currency))),
    '#prefix' => '<img src="' . url($currency->icon) . '" />',
    '#description' => t("Should be between 12px square and 16px square, preferably with a transparent background"),
  );
return $form;
}



//save the default currency to a variable
function default_currency_config_submit($form, &$form_state) {
  $cc_default_currency = variable_get('cc_default_currency', (object)array());
  $cc_default_currency->nid = 0;
  $cc_default_currency->title = $form_state['values']['title'];
  $cc_default_currency->max_balance = $form_state['values']['max_balance'];
  $cc_default_currency->min_balance = $form_state['values']['min_balance'];
  $cc_default_currency->comparative_value = $form_state['values']['comparative_value'];
  $cc_default_currency->zero_offset = intval($form_state['values']['zero_offset']);
  $cc_default_currency->division = $form_state['values']['division'];
  //need to test this
  if ($form_state['values']['icon']) {
    $cc_default_currency->icon = $form_state['values']['icon'];
  }
  variable_set('cc_default_currency', $cc_default_currency);
}

function transaction_quality_config(){
  $saved_array=variable_get('cc_transaction_qualities', array());
  foreach ($saved_array as $key => $word) {
    $default_string .= $key.'|'.$word . "\n";
  }
  $form['cc_transaction_qualities'] = array(
    '#title' => 'Possible ratings',
    '#type' => 'textarea',
    '#default_value' => $default_string,
    '#description' => t('Traders can earn a reputation when their customers rate them highly. Use this field to list the words used to describe the quality of transactions, from worst to best, one on each line.').
      t('Example:<br />-1|Bad<br />0|As expected<br />1|Great!<br />  Leave blank to disable this feature. Note that in automated transactions, 0 will be be used, so 0 should be a neutral value.').
      t('Beware that changing the number of rating options after transactions have been entered will distort the averages.').
      t('This feature could use a lot more work to integrate into views and stats'));
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

//options might equal array('state'=> TRANSACTION_STATE_COMPLETED, 'type'=>'auto', 'rating' => '4', 'starter_uid' => 55)
function generate_transaction_node($title, $payer_uid, $payee_uid, $quantity, $options=array(), $cid=0){
  if ($quantity < 0) {
    drupal_set_message(t('@function was asked to create a transaction with negative value: @value', array('@function' => 'generate_transaction_node', '@value' => $quantity)));
    return;
  } elseif ($quantity <= 0) return;
  
  global $user;
  $node = new stdClass();
  $node->title = $title;
  $node->type = 'transaction';
  $node->uid = $options['owner'] or $node->uid = $user->uid;
  $node->created = $options['created'];
  $node->status = 0; //this adds protection against anonymous users seeing transactions

  //transaction properties
  $node->cid = $cid;
  $node->payee_uid = $payee_uid;
  $node->payer_uid = $payer_uid;
  $node->quantity = $quantity;
  $node->quality = $options['rating']; //NULL if not specified
  $node->state = $options['state'] or $node->state = TRANSACTION_STATE_COMPLETED;
  $node->transaction_type = $options['type'] or $node->transaction_type = 'api';
  
  //starter and completer can be either way around if the transaction is complete and starter not provided
  $node->starter_uid = $options['starter_uid'] or $node->starter_uid = $node->payee_uid;
  if ($node->starter_uid == $node->payee_uid) {
    $node->completer_uid = $node->payer_uid;
  } else {
    $node->completer_uid = $node->payee_uid;
  }
  node_save($node);
  return $node;
}

function cc_balances_refresh(){
  module_load_include('inc', 'transactions');
  db_query("TRUNCATE table {cc_balance_cache}");
  //get a list of all the users and all the currencies they have traded in 
  $result = db_query('SELECT uid FROM {users}');
  while ($account = db_fetch_array($result)) {
    $members[] = $account['uid'];
  }
  foreach ($members as $uid) {
    $currencies = currencies_load(array('uids'=>array($uid)));
    foreach (array_keys($currencies) as $cid) {
      _recalculate_balances(array($uid), $cid);
    }
  }
  drupal_set_message(t('Flushed balance cache'));
}

/*
 * if even one currency has float values, we need to change the whole database, and back again
 */
function alter_quantity_field_types($type) {
  $newfield['type'] = $type;
  
  $newfield['description'] = "number of units transacted, ($type)";
  db_change_field($ret, 'cc_transactions', 'quantity', 'quantity', $newfield);
  
  $newfield['description'] = "sum of all completed transactions for this user in this currency, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'balance', 'balance', $newfield);
    
  $newfield['description'] = "sum of all pending transactions, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'pending_difference', 'pending_difference', $newfield);
    
  $newfield['description'] = "balance including pending transactions, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'pending_balance', 'pending_balance', $newfield);
    
  $newfield['description'] = "the sum of all this user's income in this currency, ($type)";
  db_change_field($ret, 'cc_balance_cache', 'gross_income', 'gross_income', $newfield);
}

function marketplace_report() {
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
              if (is_int($k) && $prefix != null) { 
                  $k = urlencode($prefix . $k); 
              } 
              if ((!empty($key)) || ($key === 0))  $k = $key.'['.urlencode($k).']'; 
              if (is_array($v) || is_object($v)) { 
                  array_push($ret, http_build_query($v, '', $sep, $k)); 
              } else { 
                  array_push($ret, $k.'='.urlencode($v)); 
              } 
          } 
          if (empty($sep)) $sep = ini_get('arg_separator.output'); 
          return implode($sep, $ret); 
      }// http_build_query 
  }
  $url = 'http://www.communityforge.net/registration.php?' . http_build_query($data, '', '&');
  $reply = drupal_http_request($url);
  print $reply->data;
}


//helper function for testing
function generate_transactions($howmany){
  //get a list of all user ids and currency Ids
  $results1 = db_query("SELECT uid FROM {users} WHERE uid != 1");
  $results2 = db_query("SELECT nid FROM {node} WHERE type = 'currency'");
  while ($result = db_fetch_object($results1)){
    $all_users[] = $result->uid;
  }
  while ($result = db_fetch_object($results2)){
    $all_currencies[] = $result->nid;
  }
  $usercount = count($all_users);
  $currencycount = count($all_currencies);
  $options = array(
    'type' => 'auto-generate',
  );
  while ($i < $howmany) {
    $i++;
    $options['created'] = time()-3600*24*rand(1,60);
    generate_transaction_node('autogenerated transaction', $all_users[rand(0,$usercount-1)], $all_users[rand(0,$usercount-1)], rand(1, 50), $options, $all_currencies[rand(0,$currencycount-1)]);
  }
  cc_balances_refresh();
}