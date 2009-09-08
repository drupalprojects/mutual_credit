<?php
//the URL cannot be longer than 2048, allow 200 for everything else and we have 1850 chars for the $chd
//if each point is 8 chars, then max number of points is 1850/8 = 230 with safety margin
//this number is not useful if there is more than one currency

//this is calucluated as 2048 max chars in a google charts url
// - 250 characters for all the other data
// divided by 13 characters per point
define (MAX_CHART_POINTS, 140);
define (TRANSACTIONS_PAGER_ELEMENT, 1);

/*
 * Menu Callbacks
 */

//This makes the bureau page, but also contains most of the usual CALLBACK FUNCTIONS, FOR USE WITH PANELS
function transactions_bureau ($uid) {
  $account=user_load($uid);
  $balances = theme('balances', transactions_user_balances($uid));
  $pending = transactions_pending($uid);
  $history = transactions_list($account, array('pager_limit' => 25, 'running_balance' => TRUE));
  $chart_callbacks = array(
    //chart function name, then parameters, ready for call_user_func_array()
    'chart_user_balance_history' => array($account, array('legend'=>t('Balance over time'))),
    'chart_user_gross_recent_volumes' => array($account),
    'chart_balance_limits' => array($account),
  );
  return theme('bureau', $balances, $pending, $history, $chart_callbacks);
}

//this is intended for user profile tabs only, and shows the last x completed transactions
//options only apply to the statement theme, not to the view
function transactions_list($account=NULL, $options=array()) {
  if (!$account){
    $account = $GLOBALS['user'];
  }
  $options['running_balance']=TRUE;
  if (variable_get('cc_transaction_list', 'statement') == 'statement') {
    //from the year and the month we deduce the time limits for the query
    $transactions = _get_transactions_for_user($account->uid, $options);
    foreach ($transactions as $nid=>$transaction) {
      if (!transactions_access('view', NULL, NULL, $transaction)){
        unset ($transactions[$nid]);
      }
    }
    return theme('statement', $account, $transactions);
  } else {
    $view1 = views_get_view('cc_ins_and_outs');
    //this only ever happens in one path at the moment - on the user profile tab, so we can reference the args by number
    return $view1->execute_display('default', array($account->uid, arg(4), arg(5)));
  }
}

//gets the cached balances from their own db table and returns an array of the form $var[$uids][$cids][balance|pending_difference|pending_balance|gross_income|mean]
function transactions_user_balances ($uid) {
  //get all the currency rows for the given member
  $result = db_query("
    SELECT uid, cid, balance, pending_difference, pending_balance, gross_income, gross_expenditure, quality_mean 
    FROM {cc_balance_cache} 
    WHERE uid = %d",
    $uid
  );
  while ($row = db_fetch_array($result)) {
    $balances[$row['cid']] = $row;
  }
  if ($balances) return $balances;
  
  //if the user has no balance yet, identify the default currency and populate it with zeros 
  //could sql help with this by providing a default?
  $default_currency = currency_load();
  $balances = array($default_currency->cid => array(
    'balance'=>0,
    'pending_difference' => 0,
    'pending_balance'=>0, 
    'gross_income'=>0,
    'gross_expenditure'=>0,
  ));
}

/*
 * takes a uid and returns all the transactions which that user started or need to complete, themed
 */
function transactions_pending($uid,  $is_starter = TRUE, $is_completer = TRUE) {
  //get all the pending transactions for this user
  $transactions = _get_transactions_for_user($uid, array('states' => array(TRANSACTION_STATE_PENDING)));
  
  //we might only want to show the ones the user started, or should complete
  while (list($nid,$transaction) = each($transactions)) {    
    if (transactions_access('view', $nid, $GLOBALS['user']->uid, $transaction)) {
      if ($transaction->starter_uid == $uid && $is_starter) {
        $waiting_on_other[] = _transaction_load_objects($transaction);
      } else 
      if ($transaction->completer_uid == $uid && $is_completer) {
        $waiting_on_user[] = _transaction_load_objects($transaction);
      } else
      druapl_set_message('wrong transactions selected', 'error');      
    }
  }
  return theme('pending', user_load($uid), $waiting_on_user, $waiting_on_other);
}

function show_stats($timestring, $theme = 'page') {
  $highest_incomes = cache_get($timestring . '-' . 'highest_incomes');
  $highest_expenditures = cache_get($timestring . '-' . 'highest_expenditures');
  $highest_volumes = cache_get($timestring . '-' . 'highest_volumes');
  $trades_per_user = cache_get($timestring . '-' . 'trades_per_user');
  $misc = cache_get($timestring . '-' . 'misc');
  return theme('stats_'.$theme, array(
    'highest_incomes' => $highest_incomes->data,
    'highest_expenditures' => $highest_expenditures->data,
    'highest_volumes' => $highest_volumes->data,
    'trades_per_user' => $trades_per_user->data,
    'misc' => $misc->data,
  ));
}
 

/*
 * TRANSACTION FORM HANDLING
 */

//builds a generic transaction form, for use in creating or editing.
//$mode = init, summary, or edit(default)
function transaction_base_form($transaction, $mode = 'edit') {
  global $user;
  //just check, if we are here without using a button, that user can edit the form
  if ($mode == edit && !transactions_access('edit', NULL, $user->uid, $transaction)) {
    drupal_access_denied();
  }
  $form = array();
  if ($mode == 'edit' && user_access('edit all transactions')) {
   $mode = 'fulledit';
    $form['#prefix'] = t('Full Edit mode. Beware not to enter contradictory information') . ' ' .
    t('The starter and the completer must be the same two users as the payer and the payee.');
  }
  $accounts = _get_all_trading_accounts(TRUE);
  if (!$accounts) return;
  
  $allowed_transactions = _permitted_transaction_types();
  $ttype = $transaction->transaction_type or $ttype = "";
  //this is only for if we use the dropdown, not the autocomplete
  $default_currency= variable_get('cc_default_currency', array());
  $payer = $transaction->payer_uid or $payer = 0;
  $payee = $transaction->payee_uid or $payee = 0;
  $starter = $transaction->starter_uid or $starter = 0;
  $completer = $transaction->completer_uid or $completer = 0;
  
  $form['title'] = array(
    '#type'=>'textfield',
    '#title'=> t('What is being paid for?'),
    '#required' => TRUE, 
    '#default_value' => $transaction->title,
    '#weight'=> -3,
    '#description' => t('What goods or services were exchanged? '),
  );
  if (variable_get('cc_description_min_words', 3) > 1) {
    $form['title']['#description'] .= '(' . t('Minimum @num words', array('@num' => $min_words)) . ')';
  }
  if ($mode == 'summary') $form['title']['#type'] = 'hidden';
  
  //set up the default, or hidden values of each trader select field
  $form['payee_uid'] = array(
    '#type'=>'hidden',
    '#default_value' => $payee,
  );
  $form['payer_uid'] = array(
    '#type'=>'hidden',
    '#default_value' => $payer,
  );
  $form['starter_uid'] = array(
    '#type' => 'hidden',
    '#value' => $starter,
  ); 
  $form['completer_uid'] = array (
    '#type' => 'hidden',
    '#default_value' => $completer,
  );
  //the selector_set is whether we are using starter/completer or payee/payer
  //if we know only one of the payer and payee, we show that set, otherwise show the other set
  if ($mode == 'fulledit')$selector_set = 'both';
  elseif ((bool)$payer != (bool)$payee) $selector_set = 'payer_payee';
  else $selector_set = 'starter_completer';
  
  if (($selector_set == 'payer_payee' || $selector_set == 'both' )){
    if (!$payee|| $selector_set == 'both' ) {
      $form['payee_uid']=array(
        '#type' => 'select',
        '#title' => t('Who is being paid?'),
        '#default_value' => $payee,
        '#weight' => -4,
        '#options' => $accounts,
        '#required' => TRUE
      );
    }
    if (!$payer|| $selector_set == 'both' ) {
      $form['payer_uid'] = array(
        '#type' => 'select',
        '#title' => t('Who is paying?'),
        '#default_value' => $payer,
        '#weight' => -5,
        '#required' => TRUE,
        '#options' => $accounts
      );
    }
  }
  if (($selector_set == 'starter_completer' || $selector_set == 'both' )){
    if (!$starter ||  $mode =='fulledit' || (user_access('edit all transactions') && $mode != 'summary')) {
    //special users can pretend the transaction was started by someone else
      $form['starter_uid']=array(
        '#autocomplete_path' => 'user/autocomplete',
        '#type' => 'select',
        '#title' => '(' . t('Initiator, if not you') . ')',
        '#default_value' => $starter,
        '#weight' => -5,
        '#required' => TRUE,
        '#options' => $accounts,
      );
    }
    if (!$completer || $mode == 'edit' || $mode =='fulledit'  ) {
      //remove the user's own uid so they can't trade with themselves
      if (!user_access('edit all transactions')) {
        unset($accounts[$user->{uid}]); //this variable isn't used again
      }
      $form['completer_uid']=array(
        '#type' => 'select',
        '#title' => t('With whom did you transact?'),
        '#default_value' => $transaction->completer_uid,
        '#weight' => -3,
        '#options' => $accounts,
        '#required' => TRUE
      );
    }
    
    if (count($allowed_transactions) > 1 && ($mode == 'fulledit' || $ttype == '' )) {
      $allowed_transactions=array(0=>t('(Please choose..)'))+$allowed_transactions;
      $form['transaction_type'] = array(
        '#type' => 'select',
        '#title' => t('Transaction type'),
        '#default_value' => $ttype,
        '#weight'=> -5,
        '#options'=> $allowed_transactions, 
      );
    } else {
      if (!$ttype) {
        $ttype = array_pop(array_keys($allowed_transactions));
      }
      $form['transaction_type'] = array(
        '#type' => 'hidden',
        '#default_value' => $ttype,
      );
    }
  }
  
  if (user_access('edit all transactions')) {
    foreach (array_keys($allowed_transactions) as $type){
      if (substr($type, -7) == 'confirm') {
        $form['state']=array(
          '#type' => 'checkbox',
          '#title'=> t('Transaction is waiting to be confirmed).'),
          '#default_value' => $transaction->state,
          '#weight' => 9,
        );
        break;
      }
    }
  } else {
    $form['state']=array(
      '#type' => 'hidden',
      '#default_value' => $transaction->state,
    );
  }
  
  $form['quantity'] = array(
    '#type'=>'textfield',
    '#title'=> t('Quantity'),
    '#weight'=> -1,
    '#default_value' => $transaction->quantity,
    '#field_suffix' => $default_currency->title,
    '#element_validate' => array('validate_quantity'),
    '#maxlength'=> 7,
    '#size'=> 7,
    '#required' => TRUE,
  );
  if ($mode == 'summary') $form['quantity']['#type'] = 'hidden';
  
 //this is begging to be ajax because we don't know wheter there are minutes until we know the currency
  if ($default_currency->division == 'quarters'  && !module_exists('cc_currencies')) {
    $minutes = 100*($transaction->quantity - intval($transaction->quantity));
    $form['minutes'] = array (
      '#type' => 'select',
      '#title' => t('Quarters'),
      '#weight' => $minutes,
      '#default_value' => $minutes,
      '#options' => array('0' => '00 hours', '25' => '15 minutes', '50' => '30 minutes', '75' => '45 minutes'),
    );
    //these affect the quantity field:
    unset($form['quantity']['#field_suffix']);
    unset($form['quantity']['#required']);
    $form['#validate'][] = '_conjoin_minutes';
    if ($mode == 'summary') $form['minutes']['#type'] = 'hidden';
  }

  
  if ($mode == 'summary') {
    $form['description'] = array(
      '#value' => theme('transaction', $transaction, TRUE, FALSE),
      '#weight' => -10,
    ); 
  }
  //the payer has to grade the transaction
  $options=variable_get('cc_transaction_qualities', array());
  if (count($options) && (($mode != 'init' && $user->uid == $payer) || $mode == 'fulledit')) {
    $form['quality']=array(
      '#type'=>'select',
      '#title'=> t('Rate the quality of what was paid for'),
      '#default_value' => intval($transaction->quality),
      '#options'=> $options,
      '#required' => TRUE,
      '#weight'=> 3,
    );
  }
 
  //the user name selectors need to be either dropdowns or autocomletes
  if (count($accounts) > variable_get('cc_autocomplete_user_select_threshold', 100)) {
    foreach (array('starter_uid','completer_uid', 'payer_uid', 'payee_uid') as $fieldname) {
      if ($form[$fieldname]['#type'] == 'select') {
        $form[$fieldname]['#type'] = 'textfield';
        unset($form[$fieldname]['#options']);
        $form[$fieldname]['#description'] = 'Please type a username (or user ID)';
        $form[$fieldname]['#autocomplete_path'] = 'user/autocomplete';
        if ($form[$fieldname]['#default_value']){
          //do autocomplete fields even have a default value?
          $form[$fieldname]['#default_value'] = db_result(db_query("SELECT name FROM {users} WHERE uid = '%d'", $form[$fieldname]['#default_value']));
        }
        unset($form[$fieldname]['#options']);
      }
    }
  }
  
  return $form;
}


/*
 * I needed a multistep alternative to node/add/transaction form, which creates the transaction using the transactions api, not via Drual
 * Sorry to say it, but Drupal nodes just weren't flexible enough
 * TODO: Redesign this, perhaps not using the multistep in form API
 */
function transaction_start_node_form($form_state, $transaction=NULL, $presets = 'editable') {
  module_load_include('inc', 'transactions');
  
  if (!$form_state['storage']['step']){
    $form_state['storage']['step'] = 1; //this makes the form reload from POST
  } 
  if (count($form_state['values']) || $form_state['storage']['step'] > 1) {
    $transaction  = _infer_transaction_from_form($form_state['values']);
  }
  switch ($form_state['storage']['step']) {
    case 1:
      static $form;
      if ($form) return $form; //because the trasnaction is built again using drupal_rebuild_form
      if (!$transaction)$transaction = (object)array();
      if (!$transaction->starter_uid) {
        $transaction->starter_uid = $GLOBALS['user']->uid; 
      }
      $form = transaction_base_form($transaction, 'init');
      $form['quality']['#access'] = FALSE;
      $form['state']['#default_value'] = TRANSACTION_STATE_PENDING;
      
      //By pretending to be the transaction node form, we get access to the to the validate & submit hooks (and to taxonomy_form_alter)
      $form['type']['#value'] = 'transaction'; 
      $form['#node'] = (object)array('type' => 'transaction');
      //this hides the above, which otherwise renders as a field
      $form['type']['#type'] = 'value';
      
      $form['#action'] = url('transaction/add');
      //what to do with the preset fields
      if ($presets == 'disabled') {
        foreach ((array)$transaction as $prop => $v) {
          $form[$prop]['#disabled'] = TRUE;
        }
      } elseif ($presets == 'hidden') {
        foreach ((array)$transaction as $prop => $v) {
          $form[$prop]['#access'] = FALSE;
        }
      }
      break;
    case 2:
      drupal_set_title(t('Do you want to start this transaction?'));
      $form=transaction_base_form($transaction, 'summary');
      $form['previous'] = array(
        '#type' => 'submit',
        '#value' => t('Go back'),
        '#weight' => 11,
      );
      $form['#redirect'] = 'user/' . $GLOBALS['user']->uid . '/bureau';
      
      break;
  }
  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Send'),
    '#weight' => 10
  );

  $form['#validate'][] = 'transactions_start_node_validate';
  return $form;
}

/*
WHY DOES DRUPAL REBUILD THE FORM IN STEP1?
drupal_get_form( )  ../common.inc(1655) : eval()'d code:7
20  0.4797  9917200 call_user_func_array ( )  ../form.inc:102
21  0.4797  9917248 drupal_retrieve_form( ) ../form.inc:0
22  0.4798  9918264 call_user_func_array ( )  ../form.inc:366
23  0.4798  9918312 transaction_start_node_form( )

drupal_get_form( )  ../common.inc(1655) : eval()'d code:7
20  0.5474  9957376 drupal_rebuild_form( )  ../form.inc:143
21  0.5474  9957912 call_user_func_array ( )  ../form.inc:195
22  0.5474  9957912 drupal_retrieve_form( ) ../form.inc:0
23  0.5475  9958952 call_user_func_array ( )  ../form.inc:366
24  0.5475  9959072 transaction_start_node_form( )
 */

function transaction_start_node_form_submit($form, &$form_state) {
  module_load_include('admin.inc', 'transactions');
  //save the values for the current step into the storage array
  $form_state['storage']['values'][$form_state['storage']['step']] = $form_state['values'];
  
  // check the button that was clicked and action the step change
  if ($form_state['clicked_button']['#id']=='edit-previous') {
      $form_state['storage']['step']--;
  } elseif ($form_state['clicked_button']['#id']=='edit-next') {
      $form_state['storage']['step']++;
  }
  if ($form_state['storage']['step'] < 3){
    $form_state['rebuild'] = TRUE;
  } else {
    $transaction = _infer_transaction_from_form($form_state['values']);
    $options = array(
      'type' => $transaction->transaction_type,
      'state' => $transaction->state,
      'starter_uid' => $transaction->starter_uid,
      'rating' => $transaction->quality,
    );
    $node = generate_transaction_node($transaction->title, $transaction->payer_uid, $transaction->payee_uid, $transaction->quantity, $options, $transaction->cid);
    //otherwise #redirect doesn't work
    unset($form_state['storage']);
  }
}



function transactions_complete_form(&$form_state, $nid){
  $form = array();
  $form['nid'] = array(
    '#type' => 'value',
    '#value' => $nid,
  );
  $transaction = node_load($nid);
  $form['#redirect'] = 'user/' . $transaction->completer_uid . '/bureau';
  $output = confirm_form($form,
    t('Are you sure you want to complete the transaction "@description"', array('@description' => $transaction->title)),
    'user/' . $transaction->completer_uid,
    '',
    t('Complete'), t('Cancel'),
    'complete'
  );
  return $output;
}
function transactions_complete_form_submit($form, &$form_state) {
  if ($form_state['values']['complete']) {
    $transaction = node_load($form_state['values']['nid']);
    db_query('UPDATE {cc_transactions} SET state = %d WHERE nid = %d', TRANSACTION_STATE_COMPLETED, $transaction->nid);
    _recalculate_balances(array($transaction->payer_uid, $transaction->payee_uid), $transaction->cid);
    drupal_set_message(t('Transaction with @starter completed', array('@starter'=> $transaction->starter_obj->name)));
  }
}

//this is used to validate all transaction forms
function transaction_fields_validate(&$transaction) {
  if ($transaction->transaction_type == '0' ) {
    form_set_error('transaction_type', t('No transaction type selected'));
  }
  if (!check_plain($transaction->title)) {
    form_set_error('title', t('Plain text only in transaction descriptions.'));
  }
  if (str_word_count($transaction->title) < variable_get('cc_description_min_words', 4)) {
    form_set_error('title', t('Please describe the transaction in more detail'));
  }
  //check for non numeric
  if (strlen($transaction->quantity)) {
    $currency = currency_load($transaction->cid);
    if(!is_numeric($transaction->quantity)) {
      form_set_error('quantity', t('You must enter a number'));
    }
    //quantity was converted to type float by infer_transaction_from_array()
    elseif ($currency->division == 'integer' && $transaction->quantity != intval($transaction->quantity)) {
      form_set_error('quantity', t('You must enter a whole number'));
    }
  }
  if (!$transaction->quantity && !$transaction->minutes) {
    form_set_error('title', t('You must enter a whole number'));
  }
  if (!$transaction->completer_uid) {
    form_set_error('completer_uid', t('Who are you transacting with?'));
  }
}

// we might move the balance limit checking into hereif if it means we don't have to run _infer_transaction_from_form again
function validate_quantity($element, $form_state) {
  //check for negative numbers
  if ($element['#value'] < 0) {
    form_error($element, t('Negative numbers not allowed. You may be able to change the direction of the transaction'));
  }
}


/*
 * email notification
*/
function transactions_email_completer_action($transaction, $context = array()) {
  if ($node->type != 'transaction') return;
  //load the user and continue only if the completer wants notification
  $recipient = user_load($node->completer_uid);
  if ($recipient->pending_notification = FALSE) return;
  drupal_mail(
    'transactions',
    'transaction_completer',
    $recipient->mail,
    user_preferred_language($recipient),
    array(
      'transaction' => $node, 
      'template' => $context['template'],
    )
  );
}
function transactions_email_starter_action($node, $context = array()) {
  if ($node->type != 'transaction') return;
  //load the user and continue only if the completer wants notification
  $recipient = user_load($node->starter_uid);
  if ($recipient->starting_notification = FALSE) return;
  drupal_mail(
    'transactions',
    'transaction_starter',
    $recipient->mail,
    user_preferred_language($recipient),
    array(
      'transaction' => $node, 
      'template' => $context['template'],
    )
  );
}

//generates an email message telling the user that that they have a pending transaction to complete
//params needs to be array($transaction)
//TODO make this mime_mail aware
function transactions_mail($key, &$message, $params){
  $transaction = _transaction_load_objects($params['transaction']);
  $starter = $transaction->starter_obj;
  $recipient = $transaction->completer_obj;
  
  $message['subject'] = t('You have a transaction to confirm');
  $message_template = $params['template'];
  $variables = array(
  '%completer' => $recipient->name,
  '%starter' => $starter->name,
  '%summary' => strip_tags(theme('transaction', $transaction, TRUE, FALSE)),
  '%reason' => $transaction->title,
  '%profile_url' =>  url('user/'.$recipient->uid, array('absolute'=>TRUE)),
  '%pending_url' => url('user/' . $recipient->uid . '/pending', array('absolute'=>TRUE)),
  '%bureau_url' => url('user/' . $recipient->uid . '/bureau', array('absolute'=>TRUE)),
  '%unsubscribe_url' => url( 'user/' . $recipient->uid . '/edit/account', array('fragment'=>'notifications', 'absolute'=>TRUE)),
  
  //the following tokens require mime_mail to be installed
  //'%statement' => l(t('statement'), 'user/' . $recipient->uid . '/statement', array('absolute'=>TRUE)),
  //'%site_name' => l(variable_get('site_name', 'web site'), 'user/'.$recipient->uid, array('absolute'=>TRUE)),
  //we link to 'edit/account' here because usertabs is often installed, and it will fall back to 'edit' anyway
  //'%unsubscribe' => l(t('Unsubscribe'), 'user/' . $recipient->uid . '/edit/account', array('fragment'=>'notifications', 'absolute'=>TRUE)),
  //TODO make the below work. Currently, $transaction is being passed without the nid because it is only just created
  //'%transaction' => l(t('transaction'), 'node/' . $transaction->nid . '/edit', array('absolute'=>TRUE)),
  );
  $message['body'][] = strtr($message_template, $variables);
}

/*
 * THESE INTERMEDIATE LEVEL FUNCTIONS PREPARE DATA FOR THE MAIN CALLBACK FUNCTIONS, BUT NO THEMING.
 */

/*
function get_pending($uid) {
  //get incomplete transactions in which the user is involved.
  $result = db_query("SELECT nid FROM {cc_transactions}
    WHERE state = " . TRANSACTION_STATE_PENDING . " AND ( payee_uid = %d OR payer_uid = %d )", 
    $uid, $uid);
  $transactions = array();
  while ($trans = db_fetch_array($result)) {
    $transactions[] = node_load($trans['nid']);
  }
  return $transactions;
}
*/


/*
 * Visualisations
 */
//TODO This could be cached.
//$options = array('dimensions'=> '250x200', 'currencies'=>array(DEFAULT_CUR), 'legend'='', states=array(TRANSACTION_STATE_COMPLETED), 'since_unixtime' => account opened);
function chart_user_balance_history($account, $options=array()) {
  extract ($options + array(
    'dimensions'=> '250x200', 
    'currencies'=>currencies_load(array('uids'=>array($uid))), 
    'legend'=>'',
    'states' => array(TRANSACTION_STATE_COMPLETED),
    'since_unixtime' => $account->created
  ));

  //TODO Add colours to the currency properties
  $randomcolors = array('ff8800','ff0088','0088ff','8800ff','0000ff','ffff00','ff00ff','ff0000', '00ff00', '0000ff');
  
  $all_values = array(0);
  //this affects the superimposition, I think
  $currencies = array_reverse($currencies);
  foreach ($currencies as $currency){  //this loop draws one line for one currency
    $cid  = $currency->cid;
    //gets all completed transactions with a running balance
    $transactions = _get_transactions_for_user($account->uid, array('states' => $states, 'running_balance' => TRUE, 'cid' => $currency->cid));
    
     /*
    //we need to correct for the first transaction, if it was an import, and in so doing, we'll set the first point and get the baseline time
    //TODO there may be some confusion between the carried forward balance and the offset   
    $first = array_shift($transactions);
    $start_balance = $first->balance;
    if ($first->title == t("Gross Income") || $first->title == t('Gross Expenditure')) {
      //removed the first two transactions and returns the balance from after the import
      echo "adjusting for transaction imported with gross_income\n";
      echo "Please test.. this have never been done before!";
      $second = array_shift($transactions);
      $start_balance = $first->quantity -= $second->quantity;
      
    } elseif ($first->title == t('Carried forward')) {
      $start_balance = $first->balance;
    } else {
      if ($first) array_unshift($transactions, $first);
    }
    */
    if (!count($transactions)) continue;
    
    //we can now make the first point at zero time, which corresponds to the date of the user's creation
    $points = array(0 => $currency->zero_offset);
    $points = array();
    //add all the other points, one per transaction
    foreach ($transactions as $transaction){
      //one point per transaction produces diagaonal lines
      $points[$transaction->{created}] = $transaction->balance;
    }
    //add a point showing the balance constant from the last transaction until now
    $points[time()] = $transaction->balance;
    //unsmoothing mechanism, the true picture - adds intermediate points to produce perpendicular lines
    $times = array();
    $values = array();
    
    //plot the points according to how many there are
    if (count($points)*2 < MAX_CHART_POINTS) {
      $sample_method = t('Literal');
      foreach ($points as $t => $bal){
        //make two points for each point, and calibrate
        $t1 = $t - $since_unixtime;
        //we could go further to reduce the number of chars and divide the time (unixtime) by something arbitrary
        $times[]=$t1;$times[]=$t1;
        $values[]=$bal;$values[]=$bal;
      }
      //put the arrays out of alignment by one point
      array_shift($times);
      array_pop($values);
    }
    //second smoothing mechanism, resample if there are too many points
    elseif (count($points) > MAX_CHART_POINTS) {
      $sample_method = t('Sampled');
      //we can sample the array by a factor of an integer only
      $sample_frequency = ceil(count($points) / MAX_CHART_POINTS);
      //make an array with every possible value - approx one for every pixel
      $previous_time = array_shift($point_times);
      $bal = array_shift($points);
      $all_points[$previous_time] = $bal;
      while ($time = array_shift($times)) {
        while ($previous_time < $time) {//adding the in between points
          $all_points[$previous_time] = $bal;
          $previous_time++;
        }
        $bal = array_shift($points);
        $all_points[$previous_time] = $bal;
      }
      unset($points);
      //$all_points now contains a value for every pixel time interval, ready for sampling
      //we reverse this array to be sure to sample the final value, which may be only a few seconds ago
      $reverse_points = array_reverse($all_points, TRUE);
      foreach ($reverse_points as $t=>$eachpoint){
        if (fmod($j, $sample_frequency) == 0){
          $values[$t-$since_unixtime] = $eachpoint;
        }
        $j++;
      }
      $times = array_keys($values);
    } else {
      $sample_method = t('Spot');
      foreach ($points as $time => $value) {
        $time -= $since_unixtime;
        $times[] = $time;
        $values [$time] = $value;
      }
    }
    
    //make the url encoded line from the x and y values
    $lines['mainline'.$cid] = implode(',',$times).'|'.implode(',',$values);
    $line_styles['mainline'.$cid] = 2;
    $line_colors['mainline'.$cid] = array_pop($randomcolors);
    //save the values to get the max and min later
    $all_values = array_merge($all_values, $values);
  }
  if (variable_get('cc_history_chart_limits', 'trading') == 'limits') {
    _transaction_add_limits_to_user(&$account, $currency);
    $all_values['max'] = $account->limits[$cid]['max'];
    $all_values['min'] = $account->limits[$cid]['min'];
  }
  $max = max($all_values);
  $min = min($all_values);
  
  //each line has to have it's own axis scales
  foreach ($lines as $line) {
    $ds[] = implode(',',array(-1, time()-$since_unixtime ,$min,$max));
  }

  //now put the line into the google charts api format
  $params['cht'] = 'lxy';
  $params['chs'] = $dimensions;
  $params['chd'] = 't:' . implode('|', $lines);
  //optional parameters
  if ($legend)$params['chtt'] = $legend;
  $params['chds'] = implode(',',$ds);
  $params['chxt'] = 'x,y';
  $params['chxl'] = '0:|' . date('M y', $since_unixtime) . '|' . t('Now') . '|1:|' . $min . '|' . $max;
  $params['chm'] = 'r,000000,0,1,-1'; //this is called a range marker, which we are using for the zero line
  $params['chls'] = implode('|',$line_styles);
  $params['chco'] = implode(',',$line_colors);
    
  $src = 'http://chart.apis.google.com/chart?' . charts_build_query($params); 
  $title = t("Balance over time for @user", array('@user' => theme('username', user_load($uid))));
  if (strlen($src) > 2048) {
    watchdog('transactions', "Error creating balance chart: url to google charts exceeded 2048 charts.");
    drupal_set_message("Error creating balance chart: url to google charts exceeded 2048 charts.");
  }
  return '<img src="'.$src.'" id="chart" alt="' . $sample_method . ' ' . t('Balance history chart') . '" title="'.$title.'" class="gchart" />';
}

//shows a chart with 4 columns gross spending/earning, and periodic spending/earning
//$options = array('dimensions' => '150x150', 'periodstring' => '3 month', 'currency'=>Object, 'legend'=>'', states=array(TRANSACTION_STATE_COMPLETED));
function chart_user_gross_recent_volumes($account, $options=array()) {
  $default_options = array(
    'dimensions' => '150x150', 
    'periodstring' => '3 month', 
    'currency' => currency_load(), 
    'legend' => '', 
    'states' => array(TRANSACTION_STATE_COMPLETED)
  );
  extract($options + $default_options);
  
  //need to calculate the income and outgoings of the last 3 months only
  $result = db_query("SELECT * 
    FROM {cc_transactions} t 
    INNER JOIN {node} n ON t.nid = n.nid
    WHERE (payee_uid = %d OR payer_uid = %d) 
    AND created > %d 
    AND cid = %d 
    AND state IN (%s)", 
    array(':payee'=>$account->uid, 
      ':payer'=>$account->uid, 
      ':created'=>strtotime('-'.$periodstring), 
      ':cid' => $currency->cid, 
      ':states' => implode(',', $states),
    )
  );
  while ($transaction = db_fetch_object($result)) {
    if ($transaction->payee_uid == $account->uid){
      $periodic_income +=$transaction->quantity;
    } else {
      $periodic_expenditure += $transaction->quantity;
    }
  }
  
  $max_ever = max(array($account->balances['gross_income'], $account->balances['gross_expenditure']));
  $max_recent = max(array($periodic_income, $periodic_expenditure));
  $data=array();
  $data['cht']= 'bvg';
  $data['chs']= $dimensions; //chs=<width in pixels>x<height in pixels>
  $data['chds'] = '0,' . $max_ever . ',0,' . $max_recent;
  $data['chbh'] = 'a,2,10'; //chbh=<bar width>,<space between bars>,<space between groups>
  $data['chco'] = '4D89D9,C6D9FD';
  $data['chf'] =  'bg,s,EFEFEF00';
  $data['chd'] = 't:' . intval($account->balances[$currency->cid]['gross_income']) . ',' . intval($periodic_income) . '|' . intval($account->balances[$currency->cid]['gross_expenditure']) . ',' . intval($periodic_expenditure);
  //axis labels
  $data['chxt'] = 'x,y,x,r';
  $data['chxl'] = '0:|in,out|in,out|1:||'. $max_ever/2 . '|'. $max_ever . '|2:|ever|recent|3:||'. $max_recent/2 . '|'. $max_recent;
  if ($legend)$params['chtt'] = 'chtt=' . $legend;
  return '<img src="http://chart.apis.google.com/chart?' . charts_build_query($data) . '" class="gchart" />';
}

//google-o-meter showing balance between balance limits
//defaults: array('dimensions' => '200x100','currency' => currency_load(), 'legend' => '', 'labels' => array('min','max'));
function chart_balance_limits($account, $options=array()) {
  $default_options = array(
    'dimensions' => '200x100',
    'currency' => currency_load(), 
    'legend' => '', 
    'labels' => array('min','max')
  );
  extract($options + $default_options);
  _transaction_add_limits_to_user($account, $currency);
  $limits = $account->limits[$currency->cid];
  $data=array();
  $data['cht']= 'gom';
  $data['chs']= $dimensions; //chs=<width in pixels>x<height in pixels>
  $data['chd'] = 't:' . $account->balances[$currency->cid]['balance'];
  $data['chds'] = $limits['min']. ',' . $limits['max'];
  $data['chco'] = '3366FF,FFFFFF,33CC00';
  $data['chf'] =  'bg,s,FFFFFF00';
  $data['chtt'] = $labels[0] . '  ->  ' . $labels[1];
  return '<img src="http://chart.apis.google.com/chart?' . charts_build_query($data) . '" class="gchart" />';
}

//like http_build_query, but makes less mess - google understands anyway
function charts_build_query($data) {
  foreach ($data as $key=>$val) {
    $items[] = $key . '=' . $val;
  }
  return implode('&', $items);
}

/*
 * THEMING FUNCTIONS
 */
// PREPROCESSING
//this function would be a view except views can't yet do "WHERE payer_uid = $x OR payee_uid = $x"
function template_preprocess_statement(&$vars) {
  $qualities = count(variable_get('cc_transaction_qualities', null));
  $currencies = currencies_load(array('uids'=>array($uid)));
  while(list($key, $transaction) = each($vars['transactions'])) {
    //add a field to identify the user who isn't me
    if ($transaction->starter_uid == $vars['account']->uid) {
      $vars['transactions'][$key]->other_user = user_load($transaction->completer_uid);
    } else {
      $vars['transactions'][$key]->other_user = user_load($transaction->starter_uid);
    }
    $cid = $transaction->cid;
    $vars['transactions'][$key]->url = l($transaction->title, 'node/' . $transaction->nid);
    $vars['transactions'][$key]->created = date('d/m/y', $transaction->created);
    $vars['transactions'][$key]->notme = theme('username', $transaction->other_user);
    $vars['transactions'][$key]->amount = theme('money', $transaction->quantity, $currencies[$transaction->cid]);
    $vars['transactions'][$key]->balance = theme('money', $transaction->balance, $currencies[$transaction->cid]);
    if ($transaction->payee_uid == $vars['account']->uid){
      //change the sign on the money to negative
      $vars['transactions'][$key]->income = theme('money', $transaction->quantity, $currencies[$transaction->cid]);
      $vars['transactions'][$key]->quantity = - $transaction->quantity;
      $vars['transactions'][$key]->class = 'credit';
    } else {
      $vars['transactions'][$key]->expenditure = theme('money', $transaction->quantity, $currencies[$transaction->cid]);
      $vars['transactions'][$key]->class = 'debit';
    }
    $actions=array();
    $actions = _transaction_get_buttons_form($transaction);
    if ($transaction->state == TRANSACTION_STATE_COMPLETED) {
      $actions[] = '<div style="font-size:2.5em;color:green">&#x2713;</div>';//tick
    }
    $vars['transactions'][$key]->actions = implode("\n",$actions);
    if (!$qualities) unset($vars['transactions'][$key]->quality);
  }
}

/*
 * Stats from the cache
 * 
Array(
    [misc] => array('transaction_count', 'total_volume', 'active_members', 'mean_volume_active') //number of transactions on system
    [active_member_count] => 16 //number of unique traders
    [trades_per_user] => Array  ( uid => transaction count ) sorted by num of transactions descending
    [highest_incomes] => array (uid => income)  sorted by income descending
    [highest_expenditures] => array (uid => expenditure) sorted by expenditure descending
)
 */
function theme_stats_block($stats) {
  if (!$stats['misc']['transaction_count'])   return t('No statistics will be available until some transactions have happened');
  $html = '<div class="stat">' . t("Number of transactions: @num", array('@num'=> $stats['misc']['transaction_count'])) . '</div>';
  $html .= '<div class="stat">' . t("Total volume traded: @num", array('@num'=> $stats['misc']['total_volume'])) . '</div>';
  $html .= '<div class="stat">' . t("Number of trading members: @num", array('@num'=> $stats['misc']['active_members'])) . '</div>';
  $html .= '<div class="stat">' . t("Average trading volume: @num", array('@num'=> $stats['misc']['mean_volume_active'])) . '</div>';
  return '<div class="stats">' . $html .'</div>';
}

function theme_stats_page($stats) {
  if (!$stats['misc']['transaction_count'])   return t('No statistics will be available until some transactions have happened');
  $html = '<div class="stat">' . t("Number of transactions: @num", array('@num'=> $stats['misc']['transaction_count'])) . '</div>';
  $html .= '<div class="stat">' . t("Total volume traded: @num", array('@num'=> array_sum($stats['highest_incomes']))) . '</div>';
  $html .= '<div class="stat">' . t("Number of trading members: @num", array('@num'=> $stats['misc']['active_members'])) . '</div>';
  //shorten the arrays to a useful amount
  foreach ($stats['trades_per_user'] as $uid=>$count) {
    $best_traders[] = theme('username', user_load($uid), 'profile') . ' (' . $count . ')';
    if (count($best_traders) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Most trades:") . '<ol><li>' . implode("</li>\n<li>",$best_traders) . '</li></ol></div>';
  foreach ($stats['highest_incomes'] as $uid=>$count){
    $incomes[] =  theme('username', user_load($uid), 'profile') .' ('.$count.')';
    if (count($incomes) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Largest incomes:") .'<ol><li>' . implode("</li>\n<li>",$incomes) . '</li></ol></div>';
  foreach ($stats['highest_expenditures'] as $uid=>$count){
    $expenditure[] =  theme('username', user_load($uid), 'profile') .' ('.$count.')';
    if (count($expenditure) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Largest expenditure:") . '<ol><li>' . implode("</li>\n<li>", $expenditure) . '</li></ol></div>';
  
  foreach ($stats['highest_volumes'] as $uid=>$count){
    $volumes[] =  theme('username', user_load($uid), 'profile') .' ('.$count.')';
    if (count($volumes) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Highest trading volumes:") . '<ol><li>' . implode("</li>\n<li>", $volumes) . '</li></ol></div>';
  return '<div class="stats">' . $html .'</div>';
}

function theme_rating($quality, $currency){
  return $quality;
}

//for each user id passed, go through all their transactions and calculate balance, gross_income and pending amounts in their profile
//TODO need to make this work better with multiple currencies
function _recalculate_balances($ids, $cid) {
  $currency = currency_load($cid);
  $options =  array (
    'states'=>array(TRANSACTION_STATE_PENDING, TRANSACTION_STATE_COMPLETED),
    'cid' => $cid,
   );
  while ($uid = array_pop($ids)){
    $gross_income=0;
    $outgoings=0;
    $pending_dif=0;
    $pending_balance=0;
    $transactions=NULL;
    //we don't get the running balance because we're going to iterate through everything anyway
    $transactions = _get_transactions_for_user($uid, $options);
    if (count($transactions)){
    //so we work out out the balance, pending and gross_income again in more detail
      foreach($transactions as $t) {
        if ($t->state == TRANSACTION_STATE_COMPLETED){ //completed transactions
          if ($t->payer_uid == $uid){
            $gross_expenditure += $t->quantity;
          } else {
            $qualities[] = $t->quality;
            $gross_income += $t->quantity;
          }
        } elseif ($t->state == TRANSACTION_STATE_PENDING) { //unpublished transactions are pending
          if ($t->payer_uid == $uid){
            $pending_dif -= $t->quantity;
          } else {
            $pending_dif += $t->quantity;
          }
        }
      }
    }
    $balance = $gross_income-$gross_expenditure + $currency->zero_offset;;
    $pending_balance = $balance + $pending_dif;

    //gross_income must not be affected by the carried forward
    if ($transactions[0]->title == t("Carried forward") && $transactions[0]->payer_uid ==0) {
      $gross_income -= $transactions[0]->quantity;
    }
    $quality_mean = count($qualities) ? array_sum($qualities) / count($qualities) : 0;
    
    db_query('UPDATE {cc_balance_cache} 
      SET balance = %f, pending_balance = %f, pending_difference = %f, gross_income = %f, gross_expenditure = %f, quality_mean = %f
      WHERE uid = %d AND cid = %d', 
      $balance, $pending_balance, $pending_dif, $gross_income, $gross_expenditure, $quality_mean, $uid, $cid);
    if (!db_affected_rows()) {
      @db_query("INSERT INTO {cc_balance_cache}
        (uid, cid, balance, pending_balance, pending_difference, gross_income, gross_expenditure, quality_mean)
        VALUES (%d, %d, %f, %f, %f, %f, %f, %f)",
        $uid, $cid, $balance, $pending_balance, $pending_dif, $gross_income, $gross_expenditure, $quality_mean);
    }
  }
  //TODO Need to update the cache of the block showing pending transactions, which is cached by user
}


/*
 * Gets all the transactions and calculates the running balance, returning a full transaction node if required 
 * Options are (showing defaults) array (
 *   'states'=>array(TRANSACTION_STATE_COMPLETED),
 *   'until' => time() +1,
 *   'since' => 0,
 *   'cid' => all,
 *   'direction' => both, (can be in or out)
 *   'pager_limit' => 10 all transaction module transaction queries (not views) will share the same pager $element id
 *   'running_balance' = FALSE
 *   'order_by' = ASC
 *   )
 */
function _get_transactions_for_user($uid, $options){
  extract ($options);
  if ($direction == 'in') {
    $filters[] = '(payee_uid = '.$uid.')';
  } elseif ($direction == 'out') {
    $filters[] = '(payer_uid = '.$uid.')';
  } else {
    $filters[] = '(payee_uid = ' . $uid . ' OR payer_uid = ' . $uid . ')';
  }
  if ($states) {
    foreach ($states as $state) {
      $statess[] = " t.state = $state ";
    }
  } else {
    $statess[] = " t.state <> -1 ";
  }
  $filters[] = '(' . implode(' OR ', $statess) . ')';
  
  if ($until) {
    $filters[] = " n.created < $until ";
  } else {
    $until = time() + 1;
  }

  if ($since && !$running_balance) {
    $filters[] = " n.created < $since ";
  }
  
  if ($cid) {
    $filters[] = " t.cid = $cid ";
  }
  $query = 'SELECT n.title, n.nid, t.payer_uid, t.payee_uid, t.starter_uid, t.completer_uid, t.cid, t.quantity, t.quality, t.state, n.created, t.transaction_type
      FROM {node} n
      LEFT JOIN {cc_transactions} t ON t.nid = n.nid
      WHERE n.type = "transaction"
      AND %s
      ORDER BY n.created ASC
    ';
  $args = array(implode(' AND ', $filters));
  if ($pager_limit) {
    $results = pager_query($query, $pager_limit, TRANSACTIONS_PAGER_ELEMENT, NULL, $args);
  } else {
    $results = db_query($query, $args);
  }
  
  //gets all the transactions, calculates the running balances and discards transactions before the time limit
  if ($running_balance) {
    $running_balance = array(); //one balance for each currency (not used)
    //start the running balance with the zero_offset for each currency
    $currencies = currencies_load(array('uids'=>array($uid)));
    foreach ($currencies as $cid=>$currency) {
      $running_balance[$cid] = $currency->zero_offset;
    }
  }
  //now iterate through the transactions making running balances
  $done=array();
  $transactions=array();
  while ($transaction = db_fetch_object($results)) {
    $nid = $transaction->nid;
    //this query was based on views which returns one version of each node for each term applied
    //therefore we need to use array keys to ensure we're counting each transaction once only
    if (in_array($nid, $done)) continue;
    $done[] = $nid;
    if ($running_balance) {
      //add to or subtract from the running balance
      if ($transaction->payer_uid == $uid) {
        $running_balance[$transaction->cid] -= $transaction->quantity;
      } else {
        $running_balance[$transaction->cid] += $transaction->quantity;
      }
      $transaction->balance = $running_balance[$transaction->{cid}];
    }
    //we can now discard transactions before this function's time limits
    if ($transaction->created > $since && $transaction->created < $until) {
      $transactions[$nid] = $transaction;
    }
  }
  return $transactions;
}

//get a list of all user accounts from roles with permission on any of the transaction types
function _get_all_trading_accounts ($please_choose = FALSE) {  
  $fields = variable_get('cc_select_user_show', array('name'));
  foreach ($fields as $field) {
    $show_fields =' u.' . $field;
  }

  if ($please_choose) {
    $accounts[0] = t('(Please choose..)');
  } else {
    $account = array();
  }
  if ($roles = _get_trader_roles()) {
    //the fields MUST include uid as this is the key for the array we are returning. 
    $result2= db_query("SELECT u.uid, %s 
      FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid
      WHERE status = 1 AND u.uid > 0
      AND (%s)
      ORDER BY `u`.`%s` ASC",
      $show_fields, implode(' OR ', $roles), variable_get('cc_select_user_orderby', 'uid')
    );
    while ($account = db_fetch_array($result2)) {
      $display='';
      foreach ($fields as $field) {
        $display .= $account[$field] . ' ';
      }
      $accounts[$account['uid'] ]= $display;
    }
  }
  return $accounts;
}

/*
 * Returns an array of transaction_types which the user is permitted to initiate
 */
function _permitted_transaction_types() {
  $type_names = _named_transaction_types();
  $user_types = array();
  //Only the types which the user has permission for will be returned
  foreach ($type_names as $machine_name => $name) {
    if (user_access('start ' . $machine_name)) {
      $user_types[$machine_name] = $name;
    }
  }
  return $user_types;
}

function _get_trader_roles() {
  //get all the roles with a trading permission
  $result1 = db_query("SELECT rid from {permission}  WHERE perm LIKE '%outgoing_%' OR perm LIKE '%incoming_%'");
  while ($rid = db_result($result1)){
    if ($rid == 2) {
      $roles = array ('1 = 1');
      break;
    } else {
      $roles[] = " rid = $rid "; 
    }
  }
  if ($roles) return $roles;
  else drupal_set_message(t('No roles have permission to start any transactions.'));
}

function _get_view_transactions_by_user($where) {
  $view = views_get_view('cc_transactions_by_user');
  //need to remove the field which is also the argument, i.e. payer_uid or payee_uid
  foreach ($view->display['default']->display_options['fields'] as $i => $field) {
    if ($field['relationship'] == $where['field']) {
      unset ($view->display['default']->display_options['fields'][$i]);
      break;
    }
  }
  //Change the column heading of 'amount' to be Income or Expenditure
  //and render the view with the right parameter
  if ($where['field'] == 'payer_uid') {
    $view->display['default']->display_options['fields']['quantity']['label'] = t('Expenditure');
    return $view->execute_display('default', array($where['uid']));
  } elseif ($where['field'] == 'payee_uid') {
    $view->display['default']->display_options['fields']['quantity']['label']=t('Income');
    return $view->execute_display('default', array('all', $where['uid']));
  } else{
    drupal_set_message('wrong parameter sent to _get_view_transactions_by_user');
  }
}


/*
 * This function converts the post from the transaction_start_node_form or transaction_node_form, 
 * into something like a node,
 * Otherwise see node_load()
 */
function _infer_transaction_from_form($post) {
  $post = (array)$post;
  $transaction = (object)array('type'=>'transaction');
  //process the autocomplete field
  _transactions_convert_name_uid($post);
  if ($post['starter_uid'] && $post['completer_uid']) { 
    if (substr($post['transaction_type'], 0, 8) == 'incoming') {
      //this is for flows towards the starter user
      $transaction->payer_uid  = intval($post['completer_uid']);
      $transaction->payee_uid = intval($post['starter_uid']);
    } elseif (substr($post['transaction_type'], 0, 8) == 'outgoing') {
      //flows away from the starter user
      $transaction->payer_uid = intval($post['starter_uid']);
      $transaction->payee_uid = intval($post['completer_uid']);
    }
    $transaction->starter_uid = $post['starter_uid'];
    $transaction->completer_uid = $post['completer_uid'];
  } elseif  ($post['payer_uid'] && $post['payee_uid'] && !$post['starter_uid']) {
    if (substr($post['transaction_type'], 0, 8) == 'incoming') {
      //this is for flows towards the starter user
      $transaction->starter_uid  = intval($post['payee_uid']);
      $transaction->completer_uid = intval($post['payer_uid']);
    } elseif (substr($post['transaction_type'], 0, 8) == 'outgoing') {
      //flows away from the starter user
      $transaction->starter_uid = intval($post['payer_uid']);
      $transaction->completer_uid = intval($post['payee_uid']);
    }
    $transaction->payer_uid = intval($post['payer_uid']);
    $transaction->payee_uid = intval($post['payee_uid']);
  } else {
    //leave everything as it is - infer nothing
    $transaction->payer_uid = intval($post['payer_uid']);
    $transaction->payee_uid = intval($post['payee_uid']);
    $transaction->starter_uid = intval($post['starter_uid']);
    $transaction->completer_uid = intval($post['completer_uid']);
  }
  $transaction->transaction_type = $post['transaction_type'];
  $transaction->nid = intval($post['nid']);
  $transaction->title = $post['title'];
  $transaction->quantity = (float)$post['quantity'] + $post['minutes']/100;

  $transaction->cid = intval($post['cid']);
  $transaction->quality = $post['quality'];
  $transaction->state = intval($post['state']);
  if (substr($post['transaction_type'], -7) == '_direct') {
    $transaction->state = TRANSACTION_STATE_COMPLETED;
  }
  return _transaction_load_objects($transaction);
}

//on submitting a form with autocomplete users, this converts the usernames back to uids
function _transactions_convert_name_uid(&$transaction_array, $validate_only = FALSE) {
  foreach(array('starter_uid','completer_uid', 'payer_uid', 'payee_uid') as $fieldname) {
    if (is_numeric($transaction_array[$fieldname])) continue;
    $uid = db_result(db_query("SELECT uid FROM {users} WHERE name = '%s' OR mail = '%s' ", $transaction_array[$fieldname], $transaction_array[$fieldname]));
    if ($validate_only) {
      if (!is_numeric($uid)) {
        form_set_error($fieldname, t("Please enter a member's username or email in this field: @nonuser", array('@nonuser' => $transaction_array[$fieldname])));
      }
    } else {
      $transaction_array[$fieldname] = $uid;
    }
  }
}

/*
 * some elements of the transaction are objects in themselves,
 * This process completes loading the transaction
 * whether derived from a POSTed array, or from the node_load process.
 */
function _transaction_load_objects($transaction){
  if (is_numeric($transaction->starter_uid)){//used only when validating a pre_transaction
    if (substr($array['transaction_type'], 0, 9) == 'incoming_') {//this is for flows towards the starter user
      $transaction->payer_uid  = $transaction->completer_uid;
      $transaction->payee_uid = $transaction->starter_uid;
    } elseif (substr($array['transaction_type'], 0, 9) == 'outgoing_') { //flows away from the starter user
      $transaction->payer_uid = $transaction->starter_uid;
      $transaction->payee_uid = $transaction->completer_uid;
    }
  } elseif (is_numeric($transaction->payer_uid)) {
    //unfortunately not all transactions passing this point can remember their vectors
    if (substr($array['transaction_type'], 0, 9) == 'incoming_') {//this is for flows towards the starter user
      $transaction->completer_uid =$transaction->payer_uid;
      $transaction->starter_uid = $transaction->payee_uid;
    } elseif (substr($array['transaction_type'], 0, 9) == 'outgoing_') { //flows towards the completer user
      $transaction->completer_uid = $transaction->payee_uid;
      $transaction->starter_uid = $transaction->payer_uid;
    }
  }
  $transaction->completer_obj =  user_load($transaction->completer_uid);
  $transaction->starter_obj = user_load($transaction->starter_uid);
  $transaction->currency =  currency_load($transaction->cid);
  return $transaction;
}

/*
 * default limit checker - can be overriden by another module
 * checks a transaction to see if it will put either of the users outside the currency limits.
 */

function _transaction_limit_check($transaction){
  
  $payee = user_load($transaction->payee_uid);
  $payer = user_load($transaction->payer_uid);
  
  _transaction_add_limits_to_user($payee, $transaction->currency);
  _transaction_add_limits_to_user($payer, $transaction->currency);
  
  $messages=array();
  $cid = $transaction->cid;
  if ($payer->balances[$cid]['balance'] - $transaction->quantity < $payer->limits[$cid]['min']) {
    $messages[] = t('Transaction would take !username below the minimum balance of !quantity', 
      array('!username' => theme('username', $payer),
        '!quantity' => theme('money', $payer->limits[$cid]['min'], $transaction->currency)
      )
    );
  }
  if ($payee->balances[$cid]['balance'] + $transaction->quantity > $payer->limits[$cid]['max']) {
    $messages[] = t('Transaction would take !username above the maximum balance of !quantity', 
      array('!username' => theme('username', $payee),
        '!quantity' => theme('money', $payer->limits[$cid]['max'], $transaction->currency)
      )
    );
  }
  foreach($messages as $message) {
    //accountants are not impeded by the balance limit checks
    if (user_access('edit all transactions')) {
      drupal_set_message($message, 'warning');
    } else {
      form_set_error('quantity', $message);
    }
  }
}

//adds the transaction limits for the given currency to the user object
//this could be done in hook_user('load'), but isn't needed very often
function _transaction_add_limits_to_user(&$account, $currency) {
  $data = unserialize($account->data);
  $limits=array();
  if (isset ($data['limits'][$cid]['min'])){
    $limits['min'] = $data['limits'][$cid]['min'];
  } else {
    $limits['min'] = $currency->min_balance;
  }
  if (isset ($data['limits'][$cid]['max'])){
    $limits['max'] = $data['limits'][$cid]['max'];
  } else {
    $limits['max'] = $currency->max_balance;
  }
  $account->limits[$currency->{cid}] = $limits;
}

function _transaction_email_replacements() {
  //these variables are replaced in function transactions_mail
  return array(
    '%completer' => t('the username of the recipient'),
    '%starter' => t('the username of the person who started the transaction'),
    '%summary' => t('a summary of the whole transaction'),
    '%reason' => t('description of the transaction by the starter'),
    '%pending_url' => t('web address of pending transactions page'),
    '%unsubscribe_url' => t('web address of profile page with subscription checkbox'),
    '%bureau_url' => t("web address of 'bureau' page of this user"),
    '%profile_url' => t("web address of recipient's profile page"),
  );
}

/*
 * Buttons to be attached to to transactions, anywhere
 * TODO this function should return a single form, and the redirects for each button should happen on processing
 */
function _transaction_get_buttons_form($transaction) {
  global $user;
  $buttons = array();
  if (transactions_access('complete', NULL, $user->uid, $transaction) && $transaction->state == TRANSACTION_STATE_PENDING) {
    $buttons[] = drupal_get_form('transaction_complete_button', $transaction->nid);
  }
  if (transactions_access('edit', NULL, $user->uid, $transaction)) {
    $buttons[] = drupal_get_form('transaction_update_button', $transaction->nid);
  }
  //delete button
  if (transactions_access('delete', NULL, $user->uid, $transaction)) {
    $buttons[] = drupal_get_form('transaction_delete_button', $transaction->nid);
  }
  return $buttons;
}   

function transaction_complete_button($form_state, $nid) {
  $base_form['submit']=array (
    '#type'=>'submit',
    '#value'=> t('Sign'),
    '#weight'=> 5,
    '#attributes' => array('class' => 'transaction_button'),
  );
  //we would set the transaction state to completed here, but on form submission it reloads the node and completes it because of the  'Confirm' op.
  $base_form ['#action'] = url('transaction/complete/' . $nid);
  return $base_form;
}

function transaction_update_button($form_state, $nid) {
  $base_form['submit']=array (
    '#type'=>'submit',
    '#value'=> t('Change'),
    '#weight'=> 5,
    '#attributes' => array('class' => 'transaction_button'),
  );
  //we would set the transaction state to completed here, but on form submission it reloads the node and completes it because of the  'Confirm' op.
  $base_form ['#action'] = url('node/' . $nid . '/edit');
  return $base_form;
}


function transaction_delete_button($form_state, $nid){
  $base_form['submit']=array (
    '#type'=>'submit',
    '#value'=> t('Erase'),
    '#weight'=> 5,
    '#attributes' => array('class' => 'transaction_button'),
  );
  //we would set the transaction state to completed here, but on form submission it reloads the node and completes it because of the  'Confirm' op.
  $base_form ['#action'] = url('node/' . $nid . '/delete');
  return $base_form;
}

//this turns the quantity on a form submission into a decimal incorporating the minutes
function _conjoin_minutes($form, &$form_state) {
  $vals = $form_state['values'];
  if ($vals['quantity'] != intval($vals['quantity'])) {
    form_set_error('quantity', t('Must be a whole number of hours'));
  }
  if ($vals['minutes']) {
    $form_state['values']['quantity'] .= '.' . $vals['minutes'];
    unset($form_state['values']['minutes']);
  }
}
