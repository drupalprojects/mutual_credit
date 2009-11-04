<?php
// $Id$: transactions.inc,v 1.202 2004/07/08 16:08:21 dries Exp $

/**
 * @file
 * This file contains all functions to view transaction content
 */

//this is calucluated as 2048 max chars in a google charts url
// - 250 characters for all the other data
// divided by 13 characters per point
define(MAX_CHART_POINTS, 140);
define(TRANSACTIONS_PAGER_ELEMENT, 1);

/*
 * Menu Callbacks
 */

//This makes the bureau page, but also contains most of the usual CALLBACK FUNCTIONS, FOR USE WITH PANELS
function transactions_bureau($uid) {
  $account=user_load($uid);
  $balances = transactions_user_balances($uid);
  $pending = transactions_pending($uid);
  $history = transactions_list($account, array('pager_limit' => 25, 'running_balance' => TRUE));
  return theme('bureau', $account, $balances, $pending, $history);
}

//this is intended for user profile tabs only, and shows the last x completed transactions
//options only apply to the statement theme, not to the view
function transactions_list($account=NULL, $options=array()) {
  if (!$account) {
    $account = $GLOBALS['user'];
  }
  $count_pending = variable_get('cc_count_pending', FALSE);
  if (variable_get('cc_transaction_list', 'statement') == 'statement') {
    $options = $options + array(
      'states' => array(TRANSACTION_STATE_COMPLETED),
    );
    $options['running_balance']=TRUE;
    //from the year and the month we deduce the time limits for the query
    $transactions = _get_transactions_for_user($account->uid, $options);
    foreach ($transactions as $nid => $transaction) {
      if (!transactions_access('view', NULL, NULL, $transaction)) {
        unset($transactions[$nid]);
      }
    }
    return theme('statement', $account, $transactions);
  }
  else {
    $view1 = views_get_view('cc_ins_and_outs');
    //this only ever happens in one path at the moment - on the user profile tab, so we can reference the args by number
    return $view1->execute_display('default', array($account->uid, arg(4), arg(5)));
  }
}

//gets the cached balances from their own db table and returns an array of the form:
// $var[$uids][$cids][balance|pending_difference|pending_balance|gross_income|mean]
function transactions_user_balances($uid) {
  // identify all possible currencies for this user and set balances to 0 as defaults
  //alternative would be to create zero rows in the db for every possible user/currency combo
  $currencies = currencies_load(array('uids' => array($uid)));
  
  //if the user has no balance yet, identify the default currency and populate it with zeros 
  //TODO could sql help with this by providing a default?
  foreach ($currencies as $currency) {
    $balances[$currency->nid] = array(
      'cid' => $currency->cid,
      'cleared_balance' => 0,
      'pending_balance' => 0,
      'pending_difference' => 0,
      'gross_income' => 0,
      'gross_expenditure' => 0,
      'quality_mean' => 0,
    );
  }
  //get all the currency rows for the given member
  $result = db_query("
    SELECT cid, cleared_balance, pending_difference, pending_balance, gross_income, gross_expenditure, quality_mean 
      FROM {cc_balance_cache} 
      WHERE uid = %d",
    $uid
  );
  while ($row = db_fetch_array($result)) {
    $balances[$row['cid']] = $row;
  }
  return theme('balances', $balances);
}

/*
 * takes a uid and returns all the transactions which that user started or need to complete, themed
 */
function transactions_pending($uid,  $is_starter = TRUE, $is_completer = TRUE) {
  //get all the pending transactions for this user
  $transactions = _get_transactions_for_user($uid, array('states' => array(TRANSACTION_STATE_PENDING)));
  
  //we might only want to show the ones the user started, or should complete
  while (list($nid, $transaction) = each($transactions)) {    
    if (transactions_access('view', $nid, $GLOBALS['user']->uid, $transaction)) {
      if ($transaction->starter_uid == $uid && $is_starter) {
        $waiting_on_other[] = $transaction;
      }
      else 
      if ($transaction->completer_uid == $uid && $is_completer) {
        $waiting_on_user[] = $transaction;
      }
      else druapl_set_message('wrong transactions selected', 'error');      
    }
  }
  return theme('pending', user_load($uid), $waiting_on_user, $waiting_on_other);
}

function show_stats($timestring, $theme = 'page', $cid=0) {
  $timestring = str_replace(' ', '_', $timestring);
  $highest_incomes = cache_get($cid .'-'. $timestring .'-highest_incomes');
  $highest_expenditures = cache_get($cid .'-'. $timestring .'-highest_expenditures');
  $highest_volumes = cache_get($cid .'-' . $timestring . '-highest_volumes');
  $trades_per_user = cache_get($cid .'-'. $timestring .'-trades_per_user');
  $misc = cache_get($cid .'-' . $timestring .'-misc');
  return theme('stats_'. $theme, array(
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
    $form['#prefix'] = t('Full Edit mode. Beware not to enter contradictory information') .' '.
    t('The starter and the completer must be the same two users as the payer and the payee.');
  }
  $accounts = _get_all_trading_accounts();
  if (!$accounts) return;
  
  $allowed_transactions = _permitted_transaction_types();
  $ttype = $transaction->transaction_type or $ttype = "";
  //this is only for if we use the dropdown, not the autocomplete
  $default_currency= variable_get('cc_default_currency', array());
  $payer = $transaction->payer_uid or $payer = '';
  $payee = $transaction->payee_uid or $payee = '';
  $starter = $transaction->starter_uid or $starter = $user->uid;
  $completer = $transaction->completer_uid or $completer = '';
  
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('What is being paid for?'),
    '#required' => TRUE, 
    '#default_value' => $transaction->title,
    '#weight' => -3,
    '#description' => t('What goods or services were exchanged? '),
  );
  if (variable_get('cc_description_min_words', 3) > 1) {
    $form['title']['#description'] .= '('. t('Minimum @num words', array('@num' => $min_words)) .')';
  }
  if ($mode == 'summary') $form['title']['#type'] = 'hidden';
  
  //set up the default, or hidden values of each trader select field
  $form['payee_uid'] = array(
    '#type' => 'hidden',
    '#default_value' => $payee,
  );
  $form['payer_uid'] = array(
    '#type' => 'hidden',
    '#default_value' => $payer,
  );
  $form['starter_uid'] = array(
    '#type' => 'hidden',
    '#value' => $starter,
  ); 
  $form['completer_uid'] = array(
    '#type' => 'hidden',
    '#default_value' => $completer,
  );
  //the selector_set is whether we are using starter/completer or payee/payer
  //if we know only one of the payer and payee, we show that set, otherwise show the other set
  if ($mode == 'fulledit')$selector_set = 'both';
  elseif ((bool)$payer != (bool)$payee) $selector_set = 'payer_payee';
  else $selector_set = 'starter_completer';
  
  if (($selector_set == 'payer_payee' || $selector_set == 'both' )) {
    if (!$payee|| $selector_set == 'both' ) {
      $form['payee_uid']=array(
        '#type' => 'select',
        '#title' => t('Who is being paid?'),
        '#default_value' => $payee,
        '#weight' => -4,
        '#options' => array(0 => t('(Please choose...)')) + $accounts,
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
        '#options' => array(0 => t('(Please choose...)')) + $accounts,
      );
    }
  }
  if (($selector_set == 'starter_completer' || $selector_set == 'both' )) {
    if (!$starter || $mode =='fulledit' || (user_access('edit all transactions') && $mode != 'summary')) {
    //special users can pretend the transaction was started by someone else
      $form['starter_uid']=array(
        '#autocomplete_path' => 'user/autocomplete',
        '#type' => 'select',
        '#title' => '('. t('Initiator, if not you') .')',
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
        '#default_value' => $completer,
        '#weight' => -3,
        '#options' => array(0 => t('(Please choose...)')) + $accounts,
        '#required' => TRUE
      );
    }
    
    if (count($allowed_transactions) > 1 && ($mode == 'fulledit' || $ttype == '' )) {
      //$allowed_transactions=array(0=>t('(Please choose...)'))+$allowed_transactions;
      $form['transaction_type'] = array(
        '#type' => 'select',
        '#title' => t('Transaction type'),
        '#default_value' => $ttype,
        '#weight' => -5,
        '#options' => $allowed_transactions, 
      );
    }
    else {
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
    foreach (array_keys($allowed_transactions) as $type) {
      if (substr($type, -7) == 'confirm') {
        $form['state']=array(
          '#type' => 'checkbox',
          '#title' => t('Transaction is waiting to be confirmed).'),
          '#default_value' => $transaction->state,
          '#weight' => 15,
        );
        break;
      }
    }
  }
  else {
    $form['state']=array(
      '#type' => 'hidden',
      '#default_value' => $transaction->state,
    );
  }
  
  $form['quantity'] = array(
    '#type' => 'textfield',
    '#title' => t('Quantity'),
    '#weight' => -1,
    '#default_value' => intval($transaction->quantity),
    '#field_suffix' => $default_currency->title,
    '#element_validate' => array('validate_quantity'),
    '#maxlength' => 7,
    '#required' => TRUE,
  );
  if ($mode == 'summary') $form['quantity']['#type'] = 'hidden';
  
 //this is begging to be ajax because we don't know what the division is until we know the currency
  if ($default_currency->division == 'quarters'  && !module_exists('cc_currencies')) {
    $form['division'] = array(
      '#type' => 'select',
      '#title' => t('Quarters'),
      '#weight' => $form['quantity']['#weight']+1,
      '#default_value' => 100*($transaction->quantity - intval($transaction->quantity)),
      '#options' => array('0' => '00 hours', '25' => '15 minutes', '50' => '30 minutes', '75' => '45 minutes'),
    );

    //these affect the quantity field:
    unset($form['quantity']['#field_suffix']);
    unset($form['quantity']['#required']);
    if ($mode == 'summary') $form['division']['#type'] = 'hidden';
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
      '#type' => 'select',
      '#title' => t('Rate the quality of what was paid for'),
      '#default_value' => intval($transaction->quality),
      '#options' => $options,
      '#required' => TRUE,
      '#weight' => 3,
    );
  }
 
  //the user name selectors need to be either dropdowns or autocomletes
  if (count($accounts) > variable_get('cc_autocomplete_user_select_threshold', 100)) {
    foreach (array('starter_uid', 'completer_uid', 'payer_uid', 'payee_uid') as $fieldname) {
      if ($form[$fieldname]['#type'] == 'select') {
        $form[$fieldname]['#type'] = 'textfield';
        unset($form[$fieldname]['#options']);
        $form[$fieldname]['#description'] = 'Please type a username (or user ID)';
        $form[$fieldname]['#autocomplete_path'] = 'user/autocomplete';
        if ($form[$fieldname]['#default_value']) {
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
function transaction_start_form($form_state, $transaction=NULL, $presets = 'editable') {
  module_load_include('inc', 'transactions');
  if (!$form_state['storage']['step']) {
    $form_state['storage']['step'] = 1; //this makes the form reload from POST
  } 
  if (count($form_state['values']) || $form_state['storage']['step'] > 1) {
    $transaction  = _infer_transaction_from_form($form_state['values']);
  }
  switch ($form_state['storage']['step']) {
    case 1:
      static $form;
      if ($form) return $form; //because the transaction is built again using drupal_rebuild_form
      if (!$transaction)$transaction = (object)array();
      if (!$transaction->starter_uid) {
        $transaction->starter_uid = $GLOBALS['user']->uid; 
      }
      $form = transaction_base_form($transaction, 'init');
      $form['quality']['#access'] = FALSE;
      $form['state']['#default_value'] = TRANSACTION_STATE_PENDING;
      
      //By pretending to be the transaction node form, we get access to the to the node hooks (and to taxonomy_form_alter)
      //not sure we need any of these actually, Is this what gives us nodeapi access as well?
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
      }
      elseif ($presets == 'hidden') {
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
      $form['#redirect'] = 'user/'. $transaction->starter_uid .'/bureau';
      
      break;
  }
  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Send'),
    '#weight' => 10
  );

  $form['#validate'][] = 'transactions_start_validate';
  return $form;
}

/*
WHY DOES DRUPAL REBUILD THE FORM IN STEP1?
drupal_get_form( )  ../common.inc(1655) : eval()'d code:7
20  0.4797  9917200 call_user_func_array( )  ../form.inc:102
21  0.4797  9917248 drupal_retrieve_form( ) ../form.inc:0
22  0.4798  9918264 call_user_func_array( )  ../form.inc:366
23  0.4798  9918312 transaction_start_form( )

drupal_get_form( )  ../common.inc(1655) : eval()'d code:7
20  0.5474  9957376 drupal_rebuild_form( )  ../form.inc:143
21  0.5474  9957912 call_user_func_array( )  ../form.inc:195
22  0.5474  9957912 drupal_retrieve_form( ) ../form.inc:0
23  0.5475  9958952 call_user_func_array( )  ../form.inc:366
24  0.5475  9959072 transaction_start_form( )
 */

function transaction_start_form_submit($form, &$form_state) {
  module_load_include('admin.inc', 'transactions');
  //save the values for the current step into the storage array
  $form_state['storage']['values'][$form_state['storage']['step']] = $form_state['values'];
  // check the button that was clicked and action the step change
  if ($form_state['clicked_button']['#id']=='edit-previous') {
      $form_state['storage']['step']--;
  }
  elseif ($form_state['clicked_button']['#id']=='edit-next') {
      $form_state['storage']['step']++;
  }
  if ($form_state['storage']['step'] < 3) {
    $form_state['rebuild'] = TRUE;
  }
  else {
    $transaction = _infer_transaction_from_form($form_state['values']);
    $options = array(
      'transaction_type' => $transaction->transaction_type,
      'state' => $transaction->state,
      'starter_uid' => $transaction->starter_uid,
      'quality' => $transaction->quality,
    );
    $node = generate_transaction_node($transaction->title, $transaction->payer_uid, $transaction->payee_uid, $transaction->quantity, $options, $transaction->cid);
    //otherwise #redirect doesn't work
    unset($form_state['storage']);
  }
}

function transaction_complete_form(&$form_state, $nid) {
  $form = array();
  $form['nid'] = array(
    '#type' => 'value',
    '#value' => $nid,
  );
  $transaction = node_load($nid);
  $form['#redirect'] = 'user/'. $transaction->completer_uid;
  $output = confirm_form($form,
    t('Are you sure you want to complete the transaction "@description"', array('@description' => $transaction->title)),
    'user/'. $transaction->completer_uid,
    '',
    t('Complete'), t('Cancel'),
    'complete'
  );
  return $output;
}
function transaction_complete_form_submit($form, &$form_state) {
  module_load_include('admin.inc', 'transactions');
  if ($form_state['values']['complete']) {
    $transaction = node_load($form_state['values']['nid']);
    db_query('UPDATE {cc_transactions} SET state = %d WHERE nid = %d', TRANSACTION_STATE_COMPLETED, $transaction->nid);
    _recalculate_balances(array($transaction->payer_uid, $transaction->payee_uid), $transaction->cid);
    drupal_set_message(t('Transaction with @starter completed', array('@starter' => theme('username', user_load($transaction->starter_uid)))));
  }
}

//this is used to validate all transaction forms
function transaction_fields_validate(&$transaction) {
  if (!$transaction->transaction_type) {
    form_set_error('transaction_type', t('No transaction type selected'));
  }
  if ($transaction->title != check_plain($transaction->title)) {
    form_set_error('title', t('Plain text only in transaction descriptions.'));
  }
  if (str_word_count($transaction->title) < variable_get('cc_description_min_words', 4)) {
    form_set_error('title', t('Please describe the transaction in more detail'));
  }
  if ($transaction->payer_uid == $transaction->payee_uid) {
    form_set_error('payer_uid', t('You cannot trade with yourself'));
  }
  //check quantity field
  if (strlen($transaction->quantity)) {
    if (!is_numeric($transaction->quantity)) {
      form_set_error('quantity', t('You must enter a number'));
      $currency = currency_load($transaction->cid);
      if ($currency->division == 'integer') {
        if (intval($transaction->quantity) != (float)$transaction->quantity) {
          form_set_error('quantity', t('You must enter a whole number'));
        }
      }
    }
  }
  else {
    form_set_error('quantity', t('You cannot exchange nothing'));
  }
  if (!$transaction->completer_uid) {
    form_set_error('completer_uid', t('Who are you transacting with?'));
  }
}

// we might move the balance limit checking into here if if it means we don't have to run _infer_transaction_from_form again
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
function transactions_mail($key, &$message, $params) {
  $transaction = $params['transaction'];
  $starter = user_load($transaction->starter_uid);
  $recipient = user_load($transaction->completer_uid);
  
  $message['subject'] = t('You have a transaction to confirm');
  $message_template = $params['template'];
  $variables = array(
  '%completer' => $recipient->name,
  '%starter' => $starter->name,
  '%summary' => strip_tags(theme('transaction', $transaction, TRUE, FALSE)),
  '%reason' => $transaction->title,
  '%profile_url' =>  url('user/'. $recipient->uid, array('absolute' => TRUE)),
  '%pending_url' => url('user/'. $recipient->uid .'/pending', array('absolute' => TRUE)),
  '%bureau_url' => url('user/'. $recipient->uid .'/bureau', array('absolute' => TRUE)),
  '%unsubscribe_url' => url( 'user/'. $recipient->uid .'/edit/account', array('fragment' => 'notifications', 'absolute' => TRUE)),
  
  //the following tokens require mime_mail to be installed
  //'%statement' => l(t('statement'), 'user/' . $recipient->uid . '/statement', array('absolute' =>TRUE)),
  //'%site_name' => l(variable_get('site_name', 'web site'), 'user/'.$recipient->uid, array('absolute' =>TRUE)),
  //we link to 'edit/account' here because usertabs is often installed, and it will fall back to 'edit' anyway
  //'%unsubscribe' => l(t('Unsubscribe'), 'user/' . $recipient->uid . '/edit/account', array('fragment' => 'notifications', 'absolute' =>TRUE)),
  //TODO make the below work. Currently, $transaction is being passed without the nid because it is only just created
  //'%transaction' => l(t('transaction'), 'node/' . $transaction->nid . '/edit', array('absolute' =>TRUE)),
  );
  $message['body'][] = strtr($message_template, $variables);
}


/*
 * PREPROCESSING FUNCTIONS
*/

function template_preprocess_balance_history(&$vars) {//$variables = array($account, $options=array())
  $default_states = array(TRANSACTION_STATE_COMPLETED);
  if (variable_get('cc_count_pending', FALSE)) $default_states[] = TRANSACTION_STATE_PENDING;
  $vars['options'] += array(
    'currencies' => currencies_load(array('uids' => array($vars['account']->uid))), 
    'states' => $default_states, //transaction of which states to be included in the calculation
    'first_time' => $vars['account']->created,
  );
  //this affects the superimposition, I think
  $vars['currencies'] = array_reverse($vars['options']['currencies']);
  $lines=array();
  $line_styles=array();
  $line_colors=array();
  foreach ($vars['currencies'] as $currency) {
    $cid  = $currency->cid;
    //gets all completed transactions with a running balance
    $transactions = _get_transactions_for_user($vars['account']->uid, array('states' => $states, 'running_balance' => TRUE, 'cid' => $currency->cid));
    if (!count($transactions)) continue;
    $vars['points'] = array($first_time => $currency->zero_offset);
    //add all the other points, one per transaction
    foreach ($transactions as $transaction) {
      //one point per transaction produces diagaonal lines
      $vars['points'][$transaction->{created}] = $transaction->balance;
    }
    //add a point showing the balance constant from the last transaction until now
    $vars['points'][time()] = $transaction->balance;
  }
  $vars['first_time'] = $first_time;
}

//shows a chart with 4 columns gross spending/earning, and periodic spending/earning
//single currency only
//TODO Improve the performance of this function, with one SQL query each for income and outgoing, totting it up for each period
//OR Cache the image!
function template_preprocess_period_volumes($vars) {
  $vars['volumes'] = array();
  $cid = $vars['cid'];

  //firstly add the values from the user object, which store the the total since ever
  //floatval is incase the value is null
  $vars['volumes'][0][$cid]['expenditure'] = floatval($vars['account']->balances[$cid]['gross_expenditure']);
  $vars['volumes'][0][$cid]['income'] = floatval($vars['account']->balances[$cid]['gross_income']);
  
  //then add values from each stats period and currency
  $states = array(TRANSACTION_STATE_COMPLETED);
  if (variable_get('cc_count_pending', FALSE)) {
    $states[] = TRANSACTION_STATE_PENDING;
  }
  
  foreach (variable_get('cc_stats_periods', array()) as $period) {
    //need to calculate the income and outgoings for the period
    $result = db_query("SELECT * 
      FROM {cc_transactions} t 
      INNER JOIN {node} n ON t.nid = n.nid
      WHERE (payee_uid = %d OR payer_uid = %d) 
      AND created > %d 
      AND cid = %d 
      AND state IN (%s)", 
      array(':payee' => $vars['account']->uid, 
        ':payer' => $vars['account']->uid,
        ':created' => strtotime('-'. $period),
        ':cid' => $cid, 
        ':states' => implode(',', $states),
      )
    );
    $vars['volumes'][$period][$cid] = array('income' => 0, 'expenditure' => 0);
    while ($transaction = db_fetch_object($result)) {
      if ($transaction->payee_uid == $vars['account']->uid) {
        $vars['volumes'][$period][$cid]['income'] += $transaction->quantity;
      }
      else {
        $vars['volumes'][$period][$cid]['expenditure'] += $transaction->quantity;
      }
    }
  }
}

//google-o-meter showing balance between balance limits
//defaults options see below
function template_preprocess_balance_limits(&$vars) {
  $vars['max'] = $vars['account']->balances[$vars['cid']]['max'];
  $vars['min'] = $vars['account']->balances[$vars['cid']]['min'];
  $vars['balance'] = floatval($vars['account']->balances[$vars['cid']]['cleared_balance']);
}


//this function would be a view except views can't yet do "WHERE payer_uid = $x OR payee_uid = $x"
function template_preprocess_statement(&$vars) {
  $currencies = currencies_load(array('uids' => array($vars['account']->uid)));
  while (list($key, $transaction) = each($vars['transactions'])) {
    //changing the transaction held in $vars into an array
    $vars['transactions'][$key] = (array)$transaction;
    $vars['transactions'][$key]['amount'] = theme('money', $transaction->quantity, $transaction->cid);
    $vars['transactions'][$key]['balance'] = theme('money', $transaction->balance, $transaction->cid);
    if ($transaction->payee_uid == $vars['account']->uid) {
      //change the sign on the money to negative
      $vars['transactions'][$key]['income'] = theme('money', $transaction->quantity, $transaction->cid);
      $vars['transactions'][$key]['class'] = 'credit';
    }
    else {
      $vars['transactions'][$key]['expenditure'] = theme('money', $transaction->quantity, $transaction->cid);
      $vars['transactions'][$key]['class'] = 'debit';
      //negate the quantity  (and derived amount)
      $vars['transactions'][$key]['quantity'] = -$transaction->quantity;
    }
    $vars['transactions'][$key] += preprocess_transaction_fields($vars['account'], $transaction);
  }
}

function template_preprocess_balances(&$vars) {
  foreach ($vars['balances'] as $cid => $values) {
    $currencies[$cid] = currency_load($cid);
  }
  foreach ($vars['balances'] as $cid => $cached_balances) {
    $vars['balances'][$cid]['cleared_balance'] = theme('money', floatval($cached_balances['cleared_balance']), $cid);
    $vars['balances'][$cid]['pending_difference'] = theme('money', floatval($cached_balances['pending_difference']), $cid);
    $vars['balances'][$cid]['pending_balance'] = theme('money', floatval($cached_balances['pending_balance']), $cid);
    $vars['balances'][$cid]['gross_income'] = theme('money', floatval($cached_balances['gross_income']), $cid);
    $vars['balances'][$cid]['gross_expenditure'] = theme('money', floatval($cached_balances['gross_expenditure']), $cid);
    $vars['balances'][$cid]['rating'] = theme('rating', floatval($cached_balances['rating']), $cid);
  }
}

function template_preprocess_transaction(&$vars) {
  if (!$vars['transaction']->created) {
    $vars['transaction']->created = time();
  }
  //and we present the main transaction fields as variables in their own rights
  $vars += preprocess_transaction_fields($vars['account'], $vars['transaction']);
  $vars['transaction'] = (array)$vars['transaction']; // because the themeing layer sees transaction as an array, not an object
}

function preprocess_transaction_fields($account, $transaction) {
  $vars['submitted'] = format_date($transaction->created, 'short');
  $vars['description'] = $transaction->title;
  $vars['starter'] = theme('username', user_load($transaction->starter_uid));
  $vars['completer'] = theme('username', user_load($transaction->completer_uid));
  $vars['amount'] = theme('money', $transaction->quantity, $transaction->cid);
  $vars['balance'] = theme('money', $transaction->balance, $transaction->cid);

  if (count($qualities = variable_get('cc_transaction_qualities', array()))) {
    $vars['rating'] = $qualities[$transaction->quality];
    $vars['transactions'][$key]['classes'] .= 'quality'. $transaction->quality;
  }

  if ($vars->starter_uid == $transaction->payer_uid) {
    $vars['payer'] = $vars['starter'];
    $vars['payee'] = $vars['completer'];
  }
  else {
    $vars['payee'] = $vars['starter'];
    $vars['payer'] = $vars['completer'];
  }
  if ($transaction->starter_uid == $account->uid) {
    $vars['notme'] = $vars['completer'];
  }
  elseif ($transaction->completer_uid == $account->uid) {
    $vars['notme'] = $vars['starter'];
  }
  if ($transaction->nid) {
    $vars['transaction_link'] = l($transaction->title, 'node/'. $transaction->nid);
  }
  else {
    $vars['transaction_link'] = $transaction->title;
  }
  if ($transaction->nid) {
    $actions = _transaction_get_buttons($transaction);
    if ($transaction->state == TRANSACTION_STATE_COMPLETED) {
      $actions[] = '<div style="font-size:2.5em;color:green">&#x2713;</div>';//tick //not worth a theming function of its own?
    }
    $vars['actions'] = implode("\n", $actions);
  }
  return $vars;
}


/*
 * Stats from the cache
 * 
Array(
    [misc] => array(
      'transaction_count', 
      'total_volume', 
      'active_members', 
      'mean_volume_active', //number of transactions on system
    ) 
    [active_member_count] => 16 //number of unique traders
    [trades_per_user] => Array  ( uid => transaction count ) sorted by num of transactions descending
    [highest_incomes] => array(uid => income)  sorted by income descending
    [highest_expenditures] => array(uid => expenditure) sorted by expenditure descending
)
 */
function theme_stats_block($stats) {
  if (!$stats['misc']['transaction_count'])   return t('No statistics will be available until some transactions have happened');
  $html = '<div class="stat">'. t("Number of transactions: @num", array('@num' => $stats['misc']['transaction_count'])) .'</div>';
  $html .= '<div class="stat">'. t("Total volume traded: @num", array('@num' => $stats['misc']['total_volume'])) .'</div>';
  $html .= '<div class="stat">'. t("Number of trading members: @num", array('@num' => $stats['misc']['active_members'])) .'</div>';
  $html .= '<div class="stat">'. t("Average trading volume: @num", array('@num' => $stats['misc']['mean_volume_active'])) .'</div>';
  return '<div class="stats">'. $html .'</div>';
}

function theme_stats_page($stats) {
  if (!$stats['misc']['transaction_count'])   return t('No statistics will be available until some transactions have happened');
  $html = '<div class="stat">' . t("Number of transactions: @num", array('@num' => $stats['misc']['transaction_count'])) .'</div>';
  $html .= '<div class="stat">' . t("Total volume traded: @num", array('@num' => array_sum($stats['highest_incomes']))) .'</div>';
  $html .= '<div class="stat">' . t("Number of trading members: @num", array('@num' => $stats['misc']['active_members'])) .'</div>';
  //shorten the arrays to a useful amount
  foreach ($stats['trades_per_user'] as $uid=>$count) {
    $best_traders[] = theme('username', user_load($uid), 'profile') . ' (' . $count . ')';
    if (count($best_traders) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Most trades:") .'<ol><li>'. implode("</li>\n<li>",$best_traders) .'</li></ol></div>';
  foreach ($stats['highest_incomes'] as $uid=>$count) {
    $incomes[] =  theme('username', user_load($uid), 'profile') .' ('. $count .')';
    if (count($incomes) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Largest incomes:") .'<ol><li>'. implode("</li>\n<li>", $incomes) . '</li></ol></div>';
  foreach ($stats['highest_expenditures'] as $uid=>$count) {
    $expenditure[] =  theme('username', user_load($uid), 'profile') .' ('. $count .')';
    if (count($expenditure) == $top_how_many) break;
  }
  $html .= '<div class="stat">'. t("Largest expenditure:") .'<ol><li>'. implode("</li>\n<li>", $expenditure) . '</li></ol></div>';
  
  foreach ($stats['highest_volumes'] as $uid=>$count) {
    $volumes[] =  theme('username', user_load($uid), 'profile') .' ('. $count .')';
    if (count($volumes) == $top_how_many) break;
  }
  $html .= '<div class="stat">' . t("Highest trading volumes:") . '<ol><li>'. implode("</li>\n<li>", $volumes) .'</li></ol></div>';
  return '<div class="stats">'. $html .'</div>';
}

function theme_rating($quality, $currency) {
  return $quality;
}


/*
 * Gets all the transactions and calculates the running balance, returning a full transaction node if required 
 * Options are (showing defaults) array(
 *   'states' => array(TRANSACTION_STATE_COMPLETED),
 *   'until' => time() +1,
 *   'since' => 0,
 *   'cid' => all,
 *   'direction' => both, (can be in or out)
 *   'pager_limit' => 10 all transaction module transaction queries (not views) will share the same pager $element id
 *   'running_balance' = FALSE
 *   'order_by' = ASC
 *   )
 */
function _get_transactions_for_user($uid, $options) {
  extract($options);
  if ($direction == 'in') {
    $filters[] = '(payee_uid = '. $uid .')';
  } 
  elseif ($direction == 'out') {
    $filters[] = '(payer_uid = '. $uid .')';
  }
  else {
    $filters[] = '(payee_uid = '. $uid .' OR payer_uid = '. $uid .')';
  }
  if ($states) {
    foreach ($states as $state) {
      $statess[] = " t.state = $state ";
    }
  }
  else {
    $statess[] = " t.state = " . TRANSACTION_STATE_COMPLETED;
  }
  $filters[] = '('. implode(' OR ', $statess) .')';
  
  if ($until) {
    $filters[] = " n.created < $until ";
  }
  else {
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
  }
  else {
    $results = db_query($query, $args);
  }
  
  //gets all the transactions, calculates the running balances and discards transactions before the time limit
  if ($running_balance) {
    $running_balance = array(); //one balance for each currency (not used)
    //start the running balance with the zero_offset for each currency
    $currencies = currencies_load(array('uids' => array($uid)));
    foreach ($currencies as $cid => $currency) {
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
      } 
      else {
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
function _get_all_trading_accounts() {  
  if ($roles = user_roles(TRUE, 'ing_')) {
    $select_fields[] = 'u.uid';
    $show_fields = variable_get('cc_select_user_show', array('name'));
    foreach ($show_fields as $field) {
      $select_fields[] =' u.'. $field;
    }
    $account = array();
    $conditions[] = '1 = 1';
    foreach ($roles as $rid => $role) {
      //role 2, authenticated user, is not stored in the roles table
      if ($rid != DRUPAL_AUTHENTICATED_RID)  $conditions[] = 'rid = '. $rid;
    }
    //the fields MUST include uid as this is the key for the array we are returning. 
    $result2= db_query("SELECT %s 
      FROM {users} u LEFT JOIN {users_roles} ur ON u.uid = ur.uid
      WHERE status = 1 AND u.uid > 0
      AND (%s)
      ORDER BY `u`.`%s` ASC",
      implode(', ', $select_fields), implode(' OR ', $conditions), variable_get('cc_select_user_orderby', 'uid')
    );
    while ($account = db_fetch_array($result2)) {
      $display='';
      foreach ($show_fields as $field) {
        $display .= $account[$field] .' ';
      }
      $accounts[$account['uid'] ]= $display;
    }
    return $accounts;
  }
  drupal_set_message(t('No roles have permission to trade. Please enable some permissions: '). l('admin/user/permissions', 'admin/user/permissions'), 'error');
}

/*
 * Returns an array of transaction_types which the user is permitted to initiate
 */
function _permitted_transaction_types() {
  $type_names = _named_transaction_types();
  $user_types = array();
  //Only the types which the user has permission for will be returned
  foreach ($type_names as $machine_name => $name) {
    if (user_access('start '. $machine_name)) {
      $user_types[$machine_name] = $name;
    }
  }
  return $user_types;
}

function _get_view_transactions_by_user($where) {
  $view = views_get_view('cc_transactions_by_user');
  //need to remove the field which is also the argument, i.e. payer_uid or payee_uid
  foreach ($view->display['default']->display_options['fields'] as $i => $field) {
    if ($field['relationship'] == $where['field']) {
      unset($view->display['default']->display_options['fields'][$i]);
      break;
    }
  }
  //Change the column heading of 'amount' to be Income or Expenditure
  //and render the view with the right parameter
  if ($where['field'] == 'payer_uid') {
    $view->display['default']->display_options['fields']['quantity']['label'] = t('Expenditure');
    return $view->execute_display('default', array($where['uid']));
  }
  elseif ($where['field'] == 'payee_uid') {
    $view->display['default']->display_options['fields']['quantity']['label']=t('Income');
    return $view->execute_display('default', array('all', $where['uid']));
  }
  else{
    drupal_set_message('wrong parameter sent to _get_view_transactions_by_user');
  }
}


/*
 * This function converts the post from the transaction_start_form or transaction_node_form, 
 * into something like a node,
 * Otherwise see node_load()
 */
function _infer_transaction_from_form($post) {
  $post = (array)$post;
  $transaction = (object)array('type' => 'transaction');
  //process the autocomplete field
  _transactions_convert_name_uid($post);
  if ($post['starter_uid'] && $post['completer_uid']) { 
    if (substr($post['transaction_type'], 0, 8) == 'incoming') {
      //this is for flows towards the starter user
      $transaction->payer_uid  = intval($post['completer_uid']);
      $transaction->payee_uid = intval($post['starter_uid']);
    }
    elseif (substr($post['transaction_type'], 0, 8) == 'outgoing') {
      //flows away from the starter user
      $transaction->payer_uid = intval($post['starter_uid']);
      $transaction->payee_uid = intval($post['completer_uid']);
    }
    $transaction->starter_uid = $post['starter_uid'];
    $transaction->completer_uid = $post['completer_uid'];
  }
  elseif  ($post['payer_uid'] && $post['payee_uid'] && !$post['starter_uid']) {
    if (substr($post['transaction_type'], 0, 8) == 'incoming') {
      //this is for flows towards the starter user
      $transaction->starter_uid  = intval($post['payee_uid']);
      $transaction->completer_uid = intval($post['payer_uid']);
    }
    elseif (substr($post['transaction_type'], 0, 8) == 'outgoing') {
      //flows away from the starter user
      $transaction->starter_uid = intval($post['payer_uid']);
      $transaction->completer_uid = intval($post['payee_uid']);
    }
    $transaction->payer_uid = intval($post['payer_uid']);
    $transaction->payee_uid = intval($post['payee_uid']);
  }
  else {
    //just convert everything to integers
    $transaction->payer_uid = intval($post['payer_uid']);
    $transaction->payee_uid = intval($post['payee_uid']);
    $transaction->starter_uid = intval($post['starter_uid']);
    $transaction->completer_uid = intval($post['completer_uid']);
  }
  $transaction->transaction_type = $post['transaction_type'];
  $transaction->nid = intval($post['nid']);
  $transaction->title = $post['title'];
  $transaction->quantity = intval($post['quantity']) + $post['division']/100;
  $transaction->cid = intval($post['cid']);
  $transaction->quality = $post['quality'];
  $transaction->state = intval($post['state']);
  if (substr($post['transaction_type'], -7) == '_direct') {
    $transaction->state = TRANSACTION_STATE_COMPLETED;
  }
  return $transaction;
}

//on submitting a form with autocomplete users, this converts the usernames back to uids
function _transactions_convert_name_uid(&$transaction_array, $validate_only = FALSE) {
  foreach (array('starter_uid', 'completer_uid', 'payer_uid', 'payee_uid') as $fieldname) {
    if (is_numeric($transaction_array[$fieldname])) continue;
    $uid = db_result(db_query("SELECT uid FROM {users} WHERE name = '%s' OR mail = '%s' ", $transaction_array[$fieldname], $transaction_array[$fieldname]));
    if ($validate_only) {
      if (!is_numeric($uid)) {
        form_set_error($fieldname, t("Please enter a member's username or email in this field: @nonuser", array('@nonuser' => $transaction_array[$fieldname])));
      }
    }
    else {
      $transaction_array[$fieldname] = $uid;
    }
  }
}

/*
 * This process completes loading the transaction
 * whether derived from a POSTed array, or from the node_load process.
 *//*
function _transaction_load_objects($transaction) {
  if (is_numeric($transaction->starter_uid)) {//used only when validating a pre_transaction
    if (substr($array['transaction_type'], 0, 9) == 'incoming_') {//this is for flows towards the starter user
      $transaction->payer_uid  = $transaction->completer_uid;
      $transaction->payee_uid = $transaction->starter_uid;
    }
    elseif (substr($array['transaction_type'], 0, 9) == 'outgoing_') { //flows away from the starter user
      $transaction->payer_uid = $transaction->starter_uid;
      $transaction->payee_uid = $transaction->completer_uid;
    }
  }
  elseif (is_numeric($transaction->payer_uid)) {
    //unfortunately not all transactions passing this point can remember their vectors
    if (substr($array['transaction_type'], 0, 9) == 'incoming_') {//this is for flows towards the starter user
      $transaction->completer_uid =$transaction->payer_uid;
      $transaction->starter_uid = $transaction->payee_uid;
    }
    elseif (substr($array['transaction_type'], 0, 9) == 'outgoing_') { //flows towards the completer user
      $transaction->completer_uid = $transaction->payee_uid;
      $transaction->starter_uid = $transaction->payer_uid;
    }
  }
  return $transaction;
}
*/
/*
 * default limit checker - can be overriden by another module
 * checks a transaction to see if it will put either of the users outside the currency limits.
 */

function _transaction_limit_check($transaction) {
  $currency = currency_load($transaction->cid);
  $payee = user_load($transaction->payee_uid);
  $payer = user_load($transaction->payer_uid);

  $messages=array();
  $cid = $transaction->cid;
  if ($payer->balances[$cid]['balance'] - $transaction->quantity < $payer->balances[$cid]['min']) {
    $messages[] = t('Transaction would take !username below the minimum balance of !quantity', 
      array('!username' => theme('username', $payer),
        '!quantity' => theme('money', $payer->balances[$cid]['min'], $transaction->cid)
      )
    );
  }
  if ($payee->balances[$cid]['balance'] + $transaction->quantity > $payer->balances[$cid]['max']) {
    $messages[] = t('Transaction would take !username above the maximum balance of !quantity', 
      array('!username' => theme('username', $payee),
        '!quantity' => theme('money', $payer->balances[$cid]['max'], $transaction->cid)
      )
    );
  }
  foreach ($messages as $message) {
    //accountants are not impeded by the balance limit checks
    if (user_access('edit all transactions')) {
      drupal_set_message($message, 'warning');
    }
    else {
      form_set_error('quantity', $message);
    }
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
 * TODO this function should return a single form, and the redirects for each button should happen on processing
 */
function _transaction_get_buttons($transaction) {
  global $user;
  $buttons = array();

  if (transactions_access('complete', NULL, $user->uid, $transaction) && $transaction->state == TRANSACTION_STATE_PENDING) {
    $buttons[] = l(t('Sign'), 'transaction/'. $transaction->nid .'/complete', array('attributes' => array('class' => 'transaction-button complete')));
  }
  if (transactions_access('edit', NULL, $user->uid, $transaction)) {
    $buttons[] = l(t('Change'), 'node/'. $transaction->nid .'/edit', array('attributes' => array('class' => 'transaction-button edit')));
  }
  //delete button
  if (transactions_access('delete', NULL, $user->uid, $transaction)) {
    $buttons[] = l(t('Erase'), 'node/'. $transaction->nid .'/delete', array('attributes' => array('class' => 'transaction-button erase')));
  }
  return $buttons;
}   
