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
 * This file handles all the non-hook functions and stuff that isn't needed in other areas of the site
 * Although statistics are kept in another file, for now
 * Contains the config pages, most of the functions referred to in hook_menu and a load of other helper functions
 */

//This makes the bureau page, but also contains most of the usual CALLBACK FUNCTIONS, FOR USE WITH PANELS
function transactions_bureau ($uid) {
  $balances = theme('balances', transactions_user_balances($uid));
  $currencies = currencies_load(array('uids'=>array($uid)));
  $chart = balance_history_chart($uid, array('width'=>250, 'height'=> 200), array_keys($currencies));
  $pending = transactions_pending($uid);
  $history = transactions_list($uid);
  return theme('bureau', $balances, $chart, $pending, $history);
}

//this is intended for user profile tabs only, and shows the last x transactions
function transactions_list($uid) {
  if (!$uid){
    $uid = $GLOBALS['user']->uid;
  }

  if (variable_get('cc_transaction_list', 'statement') == 'statement') {
    //from the year and the month we deduce the time limits for the query
    $options = array(
      'states' => $states,
      'pager_limit' => 25
    );
    $transactions = _get_transactions_for_user($uid, $options, TRUE);
    foreach ($transactions as $nid=>$transaction) {
      if (!transactions_access('view', NULL, NULL, $transaction)){
        unset ($transactions[$nid]);
      }
    }
    $transactions = transactions_preprocess_statement($transactions, $uid);
    return theme('statement', $transactions, $uid);
  } else {
    $view1 = views_get_view('cc_ins_and_outs');
    //this only ever happens in one path at the moment - on the user profile tab, so we can reference the args by number
    return $view1->execute_display('default', array($uid, arg(4), arg(5)));
  }
}

//gets the cached balances from their own db table and returns an array of the form $var[$uids][$cids][balance|pending_difference|pending_balance|gross_income|mean]
function transactions_user_balances ($uid) {
  //get all the currency rows for the given member
  $result = db_query("
    SELECT uid, cid, balance, pending_difference, pending_balance, gross_income, quality_mean 
    FROM {cc_balance_cache} 
    WHERE uid = %d",
    $uid
  );
  while ($row = db_fetch_array($result)) {
    $balances[$row['cid']]= array(
      'balance'=>$row['balance'], 
      'pending_difference'=>$row['pending_difference'], 
      'pending_balance'=>$row['pending_balance'], 
      'gross_income'=>$row['gross_income'],
      'quality_mean'=>$row['quality_mean'],
    );
  }
  if ($balances) return $balances;
  
  //identify the default currency and populate it with zeros //could sql help with this by providing a default?
  $default_currency = currency_load();
  $balances = array($default_currency->cid => array(
    'balance'=>0,
    'pending_difference' => 0,
    'pending_balance'=>0, 
    'gross_income'=>0
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

function stats_page($timestring) {
  return theme('stats_page', stats($timestring));
}
function stats_summary($timestring=NULL) {
  return theme('stats_summary', stats($timestring));
}
 

//-----------------------------------
//TRANSACTION FORM HANDLING

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
  
  $allowed_transactions = _active_transaction_types();
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
  
  //need to decide whether to show the starter/completer selector set or the payeer/payee selector set
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

//OTHER FUNCTIONS WHICH OPERATE ON TRANSACTION NODES

/*
 * I needed a multistep alternative to node/add/transaction form, which creates the transaction using the transactions api, not via Drual
 * Sorry to say it, but Drupal just wasn't flexible enough 
 * $presets can be 'editable', 'disabled' or 'hidden' (coming soon)
 * TODO: Redesign this, perhaps not using the multistep in form API
 */
function transaction_start_node_form($form_state, $transaction=NULL, $presets = 'editable') {
  if (!$form_state['storage']['step']){
    $form_state['storage']['step'] = 1; //this makes the form reload from POST
  } 
  if (count($form_state['values']) || $form_state['storage']['step'] > 1) {
    $transaction  = _infer_transaction_from_array($form_state['values']);
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
      //Here's the voodoo. taxonomy_form_alter needs to believe this is a node form and checks thus:
      /*if (isset($form['type']) && isset($form['#node']) && $form['type']['#value'] .'_node_form' == $form_id) {*/
      $form['type']['#value'] = 'transaction_start';
      $form['type']['#type'] = 'value';//this hides the above, which otherwise renders as a field
      $form['#node'] = (object)array('type' => 'transaction');
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
function transaction_fields_validate($transaction) {
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
  //accountants are not impeded by the balance limit checks to follow
  //perform checks to see that the transaction doesn't take either party outside of the currency limits
  if ($message = _out_of_limits($transaction)) {
    if (user_access('edit all transactions')) {
      drupal_set_message($message, 'warning');
    } else {
      form_set_error('quantity', $message);
    }
  }
}
// we might move the balance limit checking into hereif if it means we don't have to run _infer_transaction_from_array again
function validate_quantity($element, $form_state) {
  //check for negative numbers
  if ($element['#value'] < 0) {
    form_error($element, t('Negative numbers not allowed. You may be able to change the direction of the transaction'));
  }
}

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
    $transaction = _infer_transaction_from_array($form_state['values']);
    $options = array(
      'type' => $transaction->transaction_type,
      'state' => $transaction->state,
      'starter_uid' => $transaction->starter_uid,
      'rating' => $transaction->quality,
    );
    $node = generate_transaction_node($transaction->title, $transaction->payer_uid, $transaction->payee_uid, $transaction->quantity, $options, $transaction->cid);
    
    drupal_goto('user/' . $transaction->starter_uid . '/bureau');
  }
}


/*
 * email notification
*/
function transactions_email_completer_action($transaction, $context = array()) {
  if ($node->type != 'transaction' || $node->state == TRANSACTION_STATE_COMPLETED) return;
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


//this function would be a view except views can't yet do "WHERE payer_uid = $x OR payee_uid = $x"
function transactions_preprocess_statement($transactions, $uid) {
  while(list($key, $transaction) = each($transactions)) {
    //add a field to identify the user who isn't me
    if ($transaction->starter_uid == $uid) {
      $transactions[$key]->notme = $transaction->completer_uid;
    } else {
      $transactions[$key]->notme = $transaction->starter_uid;   
    }
    //change the sign on the money to negative
    if ($transaction->payer_uid == $uid){
      $transactions[$key]->quantity = - $transaction->quantity;
    }
  }
  return $transactions;
}

 /*
  * Stats generator
  * returns an array of common stats which can be themed by a function of your choosing
  * $timestring is a positive number of days or months e.g. '3 months'
  */
//TODO make a hook for collecting stats from other modules, e.g. requack
function stats($period_string){
  //this could exclude transactions with the balancing account
  //will also need to exclude leakage, when we've programmed it
  //this could be cached
  $all_transactions=array();
  $balancing = variable_get('cc_balancing_account_num', 1);
  $result = db_query("SELECT * FROM {cc_transactions} AS t 
    LEFT JOIN {node} AS n ON t.nid = n.nid
    WHERE n.created > '%d'
    AND t.payer_uid <> %d AND t.payee_uid <> %d", 
    strtotime('-'.$period_string), $balancing, $balancing);
  while ($row = db_fetch_object($result)){
    $all_transactions[] = $row;
  }
  $stats=array();
  if ($c = count($all_transactions)) {
    $stats['transaction_count'] = $c;
    //iterate through the transactions, adding stuff up
    foreach ($all_transactions as $t){
      $earned[$t->payee_uid] = $earned[$t->payee_uid] + $t->quantity;
      $spent[$t->payer_uid] = $spent[$t->payer_uid] + $t->quantity;
      $trades[$t->payee_uid] = $trades[$t->payee_uid]+1;
      $trades[$t->payer_uid] = $trades[$t->payer_uid]+1;
    }
    $stats['active_member_count']= count ($trades);
    asort($earned);
    asort($spent);
    asort($trades);
    $stats['highest_incomes'] = array_reverse($earned, TRUE);
    $stats['highest_expenditures'] = array_reverse($spent, TRUE);
    $stats['trades_per_user'] = array_reverse($trades, TRUE);
  }
  return $stats;
}

//TODO This could be cached.
function balance_history_chart($uid, $dimensions = array('width'=>250, 'height'=>200), $cids = array(0)) {
   $cids = array_reverse($cids);
  //TODO Make a setings page for colors and size
  $randomcolors = array('ff8800','ff0088','0088ff','8800ff','0000ff','ffff00','ff00ff','ff0000', '00ff00', '0000ff');
  $this_user = user_load($uid);
  
  $options =  array (
    'states'=>array(TRANSACTION_STATE_PENDING, TRANSACTION_STATE_COMPLETED),
   );
   
  //need to indicate where zerobalance is, with a horizontal line
  $lines['zeroline'] = '-1|0,0';
  //google omits lines with either end off the scale
  $line_styles['zeroline'] = 1;
  $line_colors['zeroline'] = 'aaaaaa';
  $all_values = array(0);
  $zero_time  = $this_user->created;
  foreach ($cids as $cid){  //this loop draws one line for one currency
    $currency = currency_load($cid);
    //gets all completed transactions with a running balance
    $options['cid'] = $cid;
    $transactions = _get_transactions_for_user($uid, $options, TRUE);
    
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
        $t1 = $t - $zero_time;
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
          $values[$t-$zero_time] = $eachpoint;
        }
        $j++;
      }
      $times = array_keys($values);
    } else {
      $sample_method = t('Spot');
      foreach ($points as $time => $value) {
        $time -= $zero_time;
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
  $max = max($all_values);
  $min= min($all_values);
  
  //each line has to have it's own axis scales
  foreach ($lines as $line) {
    $ds[] = implode(',',array(-1, time()-$zero_time ,$min,$max));
  }

  //now put the line into the google charts api format
  $params['cht'] = 'cht=lxy';
  $params['chs'] = 'chs=' . implode('x',$dimensions);
  $params['chd'] = 'chd=t:' . implode('|', $lines);
  //optional parameters
  $params['chls'] = 'chls=' . implode('|',$line_styles);
  $params['chco'] = 'chco=' . implode(',',$line_colors);
  $params['chtt'] = 'chtt=' . t('Balance over time');
  $params['chds'] = "chds=" . implode(',',$ds);
  $params['axes'] = 'chxt=x,y&chxl=0:|' . date('M y', $this_user->created) . '|' . t('Now') . '|1:|' . $min . '|' . $max;
    
  $src = 'http://chart.apis.google.com/chart?'.implode('&', $params);
  $title = t("Balance over time for @user", array('@user' => theme('username', user_load($uid))));
  if (strlen($src) > 2048) {
    watchdog('transactions', "Error creating balance chart: url to google charts exceeded 2048 charts.");
    drupal_set_message("Error creating balance chart: url to google charts exceeded 2048 charts.");
  }
  return '<img src="'.$src.'" id="chart" alt="' . $sample_method . ' ' . t('Balance history chart') . '" title="'.$title.'" />';
}


/* Google Charts example

// multiple line chart. Three lines: red with blue square point, turqoise with turquoise square points, blue dashed with red square points
http://chart.apis.google.com/chart?
cht=lxy& //chart type
chs=200x125& //size
chd=t:0,30,60,70,90,95,100|20,30,40,50,60,70,80|10,30,40,45,52|100,90,40,20,10|-1|5,33,50,55,7& //coordinates x|y|x|y|x|y
chco=3072F3,ff0000,00aaaa& //colours of lines
chls=2,4,1& //line styles http://code.google.com/apis/chart/styles.html#line_styles 
chm=s,FF0000,0,-1,5|s,0000ff,1,-1,5|s,00aa00,2,-1,5 //markers http://code.google.com/apis/chart/styles.html#shape_markers2
*/

/*
 * THEMING FUNCTIONS
 */

/*
 * Stats theme functions all work on the same stats output from the stats function
 * 
Array(
    [transaction_count] => 33 //number of transactions on system
    [active_member_count] => 16 //number of unique traders
    [trades_per_user] => Array  ( uid => transaction count ) sorted by num of transactions descending
    [highest_incomes] => array (uid => income)  sorted by income descending
    [highest_expenditures] => array (uid => expenditure) sorted by expenditure descending
)
 */
function theme_stats_summary($stats) {
  if (!$stats['transaction_count'])   return t('No statistics will be available until some transactions have happened');
  $html = '<div class="stat">' . t("Number of transactions: @num", array('@num'=> $stats['transaction_count'])) . '</div>';
  $html .= '<div class="stat">' . t("Total volume traded: @num", array('@num'=> array_sum($stats['highest_incomes']))) . '</div>';
  $html .= '<div class="stat">' . t("Number of trading members: @num", array('@num'=> $stats['active_member_count'])) . '</div>';
  return '<div class="stats">' . $html .'</div>';
}

function theme_stats_page($stats) {
  if (!$stats['transaction_count'])   return t('No statistics will be available until some transactions have happened');
  $html = '<div class="stat">' . t("Number of transactions: @num", array('@num'=> $stats['transaction_count'])) . '</div>';
  $html .= '<div class="stat">' . t("Total volume traded: @num", array('@num'=> array_sum($stats['highest_incomes']))) . '</div>';
  $html .= '<div class="stat">' . t("Number of trading members: @num", array('@num'=> $stats['active_member_count'])) . '</div>';
  //shorten the arrays to a useful amount
  foreach ($stats['trades_per_user'] as $uid=>$count) {
    $best_traders[] = theme('username', user_load($uid), 'profile') . ' (' . $count . ')';
    if (count($best_traders) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Most trades:") . '<ol><li>' . implode('</li><li>',$best_traders) . '</li></ol></div>';
  foreach ($stats['highest_incomes'] as $uid=>$count){
    $incomes[] =  theme('username', user_load($uid), 'profile') .' ('.$count.')';
    if (count($incomes) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Largest incomes:") .'<ol><li>' . implode('</li><li>',$incomes) . '</li></ol></div>';
  foreach ($stats['highest_expenditures'] as $uid=>$count){
    $expenditure[] =  theme('username', user_load($uid), 'profile') .' ('.$count.')';
    if (count($expenditure) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Largest expenditure:") . '<ol><li>' . implode('</li><li>', $expenditure) . '</li></ol></div>';
  return '<div class="stats">' . $html .'</div>';
}

//$data is a mysql result containing with each row showing a transaction
function theme_statement($transactions, $uid) {
  $that_user = user_load($uid);
  if (!count($transactions)) {
    return "\n<p>".t('There are no completed transactions.')."</p>\n";
  }

  //need to delare the column headings, their order, and associated fields
  //array keys must correspond to the keys in the transaction objects
  $columns = array(
    'created' => t('Date'),
    'title' => t('Description'), 
    'notme' => t('With'),
    'quantity' => t('Amount'),
    'quality' => t('Rating'),
    'income' => t('Income'),
    'outgoing' => t('Expenditure'),
    'balance' => t('Running Total'),
    'actions' => '',
  );
  //put the given array into the columns declared to make a table
  foreach($transactions as $key => $transaction) {
    foreach ($columns as $field => $title){
      $rows[$key]['data'][$field] = $transaction->$field;
    }
  }
  //Put transactions into a multidimensional array, reformating many of the fields
  $currencies = currencies_load(array('uids'=>array($uid)));
  foreach($transactions as $key=>$transaction) {
    $actions=array();
    $cid = $transaction->cid;
    $rows[$key]['data']['title'] = l($transaction->title, 'node/' . $transaction->nid);
    $rows[$key]['data']['notme'] = theme('username', user_load($transaction->notme), 'profile');
    $rows[$key]['data']['created'] = date('d/m/y', $transaction->created);
    $rows[$key]['data']['quantity'] = theme('money', $transaction->quantity, $currencies[$cid]);
    $rows[$key]['data']['balance'] = theme('money', $transaction->balance, $currencies[$cid]);
    if ($transaction->quantity > 0) {
      $rows[$key]['data']['income'] = theme('money', $transaction->quantity, $currencies[$cid]);
      $rows[$key]['class'] = 'income';
    } else {
      $rows[$key]['data']['outgoing'] = theme('money', abs($transaction->quantity), $currencies[$cid]);
      $rows[$key]['class'] = 'outgoing';
    }
    
    $actions = _transaction_get_buttons($transaction);
    
    if ($transaction->state == TRANSACTION_STATE_COMPLETED) {
      $actions[] = '<div style="font-size:2.5em;color:green">&#x2713;</div>';//tick
    }
    $rows[$key]['data']['actions'] = implode("\n",$actions);
          
    //style the row according to the running balance
    if ($transaction->balance < 0 ) {
      $rows[$key]['class'] .= ' debit';
    } else {
      $rows[$key]['class'] .= ' credit';
    }        
  }
  return theme('table', $columns, $rows) . 
    theme('pager', NULL, 1, TRANSACTIONS_PAGER_ELEMENT);
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
    $transactions = _get_transactions_for_user($uid, $options, FALSE);
    if (count($transactions)){
    //so we work out out the balance, pending and gross_income again in more detail
      foreach($transactions as $t) {
        if ($t->state == TRANSACTION_STATE_COMPLETED){ //completed transactions
          if ($t->payer_uid == $uid){
            $outgoings += $t->quantity;
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
    $balance = $gross_income-$outgoings + $currency->zero_offset;;
    $pending_balance = $balance + $pending_dif;

    //gross_income must not be affected by the carried forward
    if ($transactions[0]->title == t("Carried forward") && $transactions[0]->payer_uid ==0) {
      $gross_income -= $transactions[0]->quantity;
    }
    $quality_mean = count($qualities) ? array_sum($qualities) / count($qualities) : 0;
    
    db_query('UPDATE {cc_balance_cache} 
      SET balance = %f, pending_balance = %f, pending_difference = %f, gross_income = %f, quality_mean = %f
      WHERE uid = %d AND cid = %d', 
      $balance, $pending_balance, $pending_dif, $gross_income, $quality_mean, $uid, $cid);
    if (!db_affected_rows()) {
      @db_query("INSERT INTO {cc_balance_cache}
        (uid, cid, balance, pending_balance, pending_difference, gross_income, quality_mean)
        VALUES (%d, %d, %f, %f, %f, %f, %f)",
        $uid, $cid, $balance, $pending_balance, $pending_dif, $gross_income, $quality_mean);
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
 *   'cid' => all
 *   'pager_limit' => 0 all transaction module transaction queries (not views) will share the same pager $element id
 *   )
 */
function _get_transactions_for_user($uid, $options, $running_balance = FALSE){
  extract ($options);
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
  $query = "SELECT DISTINCT n.title, n.nid, t.payer_uid, t.payee_uid, t.starter_uid, t.completer_uid, t.cid, t.quantity, t.quality, t.state, n.created, t.transaction_type
      FROM {node} n
      LEFT JOIN {cc_transactions} t ON t.nid = n.nid
      WHERE (payee_uid = '%d' OR payer_uid = '%d')
      AND n.type = 'transaction'
      AND %s
      ORDER BY n.created ASC
    ";
  $args = array($uid, $uid, implode(' AND ', $filters));
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
function _infer_transaction_from_array($post) {
  $transaction = (object)array();
  //process the autocomplete field
  _transactions_convert_name_uid($post);
  if ($post['starter_uid'] && $post['completer_uid']) { //
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
 * checks a transaction to see if it will put either of the users outside the currency limits.
 */
function _out_of_limits($transaction) {
  if ($transaction->starter_uid == $transaction->payee_uid) {
    $payer = $transaction->completer_obj;
    $payee = $transaction->starter_obj;
  } else {
    $payer = $transaction->starter_obj;
    $payee = $transaction->completer_obj;
  }
  // get all the balances of participants
  $cid = $transaction ->cid;
  $payer_balances = transactions_user_balances($transaction->payer_uid);
  $payee_balances = transactions_user_balances($transaction->payee_uid);
  if ($payer_balances[$cid]['balance'] - $transaction->quantity < $transaction->currency->min_balance) {
    return t('Transaction has been rejected as it would take !username below the minimum balance of !quantity', 
      array('!username' => theme('username', $payer),
        '!quantity' => theme('money', $transaction->currency->min_balance, $transaction->currency)
      )
    );
  } elseif ($payee_balances[$cid]['balance'] + $transaction->quantity > $transaction->currency->max_balance) {
    return t('Transaction has been rejected as it would take !username above the maximum balance of !quantity', 
      array('!username' => theme('username', $payee),
        '!quantity' => theme('money', $transaction->currency->max_balance, $transaction->currency)
      )
    );
  }
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
 */
function _transaction_get_buttons($transaction) {
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
