<?php
// $Id$

/**
 * @file
 * This file contains all functions to view transaction content
 */

//this is calcluated as 2048 max chars in a google charts url - 250 characters for all the other data divided by 13 characters per chart-point
define(MAX_CHART_POINTS, 140);
define(TRANSACTIONS_PAGER_ELEMENT, 1);
define(TRANSACTIONS_PER_PAGE, 20);

function transactions_nodeapi(&$node, $op) {
  if ($node->type != 'transaction') return;
  module_load_include('inc', 'transactions');
  switch ($op) {
    case 'validate':
      if (!count(form_get_errors()) && $node->payer_uid && $node->payee_uid) {
        _transaction_user_limits(user_load($node->payer_uid), -$node->quantity, intval($node->cid));
        _transaction_user_limits(user_load($node->payee_uid), +$node->quantity, intval($node->cid));
      }
      break;
      
    case 'insert':
      module_load_include('admin.inc', 'transactions');
      //gets rid of any boring status messages
      drupal_get_messages('status', TRUE);
      
      //automatically trigger the notifications
      $result = db_query("SELECT aid FROM {actions} WHERE callback = 'transactions_email_starter_action' OR callback = 'transactions_email_completer_action'");
      while ($aid = db_result($result)) {
        actions_do($aid, $node, array(
          'hook' => 'nodeapi',
          'op' => $op
        ));
      }
      // the 'break' is missing deliberately
    case 'update':
    case 'delete':
      module_load_include('admin.inc', 'transactions');
      recalculate_balances($node->payer_uid, $node->cid);
      recalculate_balances($node->payee_uid, $node->cid);
      stats_refresh(intval($node->cid));
  }
}

/*
 * Menu Callbacks
 */

//This makes the bureau page, but also contains most of the usual CALLBACK FUNCTIONS, FOR USE WITH PANELS
function transactions_bureau($account) {
  return theme('bureau', $account);
}
function template_preprocess_bureau($vars){
  $vars['balances'] = transactions_user_balances($vars['account']->uid);
  $vars['history'] = transactions_list($vars['account'], array('running_balance' => TRUE));
  foreach(currencies_load(array('uid'=>$vars['account']->uid)) as $currency) {
    $vars['balance_limits'] .= theme('balance_limits', $vars['account'], $currency->cid);
    $vars['period_volumes'] .= theme('period_volumes', $vars['account'], $currency->cid); 
  }
}

//this is intended for user profile tabs only, and shows the last x completed transactions
//options only apply to the statement theme, not to the view
function transactions_list($account=NULL, $options=array()) {
  global $pager_total, $pager_page_array;
  $page = isset($_GET['page']) ? $_GET['page'] : '';
  $pager_page_array = explode(',', $page);
  $page_no = $pager_page_array[TRANSACTIONS_PAGER_ELEMENT];


  $account = $account ? $account : $GLOBALS['user'];
  $count_pending = variable_get('cc_count_pending', FALSE);
  $options = $options + array(
    'states' => array(TRANSACTION_STATE_COMPLETED),
    'running_balance' => TRUE,
  );
  $transactions = _get_transactions_for_user($account->uid, $options);
  $pager_total[TRANSACTIONS_PAGER_ELEMENT] = ceil(count($transactions)/TRANSACTIONS_PER_PAGE);

  //check which transactions can be viewed
  foreach ($transactions as $nid => $transaction) {
    if (!transactions_access('view', NULL, NULL, $transaction)) {
      unset($transactions[$nid]);
    }
  }

  $transactions = array_reverse($transactions);
  $transactions = array_slice($transactions, $page_no*TRANSACTIONS_PER_PAGE, TRANSACTIONS_PER_PAGE);
  return theme('statement', $account, $transactions);

}


//this function would be a view except views can't yet do "WHERE payer_uid = $x OR payee_uid = $x"
function template_preprocess_statement(&$vars) {
  $uid = $vars['account']->uid;
  $currencies = currencies_load(array('uids' => array($uid)));
  while (list($key, $transaction) = each($vars['transactions'])) {
    $props = array();
    //the following can't be themed as transaction fields,
    //because they depend on whose statement we are looking at
    if ($transaction->payee_uid == $uid) {
      //change the sign on the money to negative
      $props['income'] = theme('money', $transaction->quantity, $transaction->cid);
      $classes = 'credit';
    }
    else {
      $props['expenditure'] = theme('money', $transaction->quantity, $transaction->cid);
      $classes = 'debit';
      //negate the quantity  (and derived amount)
      $transaction->quantity = -$transaction->quantity;
    }
    //balance property is only known to 'statement' page
    $props['balance'] = theme('money', $transaction->balance, $transaction->cid);
    $vars['transactions'][$key] = $props + preprocess_transaction_fields($transaction, $uid);
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
 * TRANSACTION FORM HANDLING
 */

// builds a generic transaction form, for use in creating or editing.
// $mode = init, summary, or edit(default)
// $selectorset = starter_completer | from_to | both
// Payments done with the from/to selectorset cannot be pending because the engine can't know which one is the completer
function transaction_base_form($transaction, $mode = 'edit', $selectorset = 'starter_completer') {
  global $user;
  $form = array();
  variable_get('cc_trader_selector', '') == '' ? $user_select_widget = 'textfield' : $user_select_widget = 'select';
  //this is last minute hack for node/%/edit only. Resolved in version 2
  if (arg(0)=='node') $user_select_widget = 'select'; 
  if ($user_select_widget == 'select') {
    $cached_traders = cache_get('cc_trader_list');
    $traders = $cached_traders->data;
  }
  //just check, if we are here without using a button, that user can edit the form
  if ($mode == edit && !transactions_access('edit', NULL, $user->uid, $transaction)) {
    drupal_set_message(t('You cannot edit this transaction'), 'error');
  }

  $form['#mode'] = $mode;
  $form['#selector_set'] = $selectorset;
  if ($edit_all_transactions && $mode == 'edit') { 
    $form['#selector_set'] = 'both';
  }
  //this determines the unfilled user fields in the form
  $form['#after_build'] = array('transaction_form_after_build');
  $edit_all_transactions = user_access('edit all transactions');
  $allowed_transactions = _named_transaction_types();
  $ttype = $transaction->transaction_type or $ttype = "";
  //this is only for if we use the dropdown, not the autocomplete
  $default_currency= variable_get('cc_default_currency', array());
  $payer = $transaction->payer_uid or $payer = '';
  $payee = $transaction->payee_uid or $payee = '';
  $starter = $transaction->starter_uid or $starter = '';
  $completer = $transaction->completer_uid or $completer = '';
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('What is being paid for?'),
    '#required' => TRUE, 
    '#default_value' => $transaction->title,
    '#description' => t('What goods or services were exchanged? '),
    '#element_validate' => array('cc_validate_title')
  );
  if (variable_get('cc_description_min_words', 4) > 1) {
    $form['title']['#description'] .= '('. t('Minimum @num words', array('@num' => $min_words)) .')';
  }
  $form['starter_uid'] = array(
    '#type' => 'hidden',
    '#value' => $starter,
  ); 
  $form['completer_uid'] = array(
    '#type' => 'hidden',
    '#default_value' => $completer,
  );
  $form['payee_uid'] = array(
    '#type' => 'hidden',
    '#default_value' => $payee,
  );
  $form['payer_uid'] = array(
    '#type' => 'hidden',
    '#default_value' => $payer
  );
  if ($form['#selector_set'] == 'payer_payee' || $form['#selector_set'] == 'both' ) {
    if (!$payee || ($edit_all_transactions && $mode == 'edit')) {
      $form['payee_uid']=array(
        '#type' => $user_select_widget,
        '#title' => t('Who is being paid?'),
        '#default_value' => $payee,
        '#options' => $traders ? array(0 => t('(Please choose...)')) + $traders : NULL,
        '#required' => TRUE,
        '#element_validate' => array('cc_validate_2parties'),
        '#disabled' => $mode == 'edit' && $transaction->state
      );
    }
    if (!$payer || ($edit_all_transactions && $mode == 'edit')) {
      $form['payer_uid'] = array(
        '#type' => $user_select_widget,
        '#title' => t('Who is paying?'),
        '#default_value' => $payer,
        '#options' => $traders ? array(0 => t('(Please choose...)')) + $traders : NULL,
        '#required' => TRUE,
        '#disabled' => $mode == 'edit' && $transaction->state
      );
    }
    $form['transaction_type'] = array(
      '#type' => 'hidden',
      '#default_value' => '3rdparty_confirm',
    );
    $form['#theme'] = array('transaction_3rdparty_form');
  }
  if (($form['#selector_set'] == 'starter_completer' || $form['#selector_set'] == 'both' )) {
    if (!$starter || ($edit_all_transactions && $mode == 'edit')) {
      if ($user_select_widget == 'textfield') {
        $starter_user = user_load($starter);
      }
      //special users can pretend the transaction was started by someone else
      $form['starter_uid'] = array(
        '#type' => $user_select_widget,
        '#title' => t('Initiator, (if not you)'),
        '#default_value' => $starter_user ? $starter_user->name : $starter,
        '#required' => TRUE,
        '#options' => $traders ? $traders : NULL,
      );
    }
    if (!$completer || $mode == 'edit') {
      //remove the user's own uid so they can't trade with themselves
      if (!$edit_all_transactions) {
        unset($traders[$user->{uid}]); //remove user from list of accounts shown to form
      }
      $form['completer_uid'] = array(
        '#type' => $user_select_widget,
        '#title' => t('With whom did you transact?'),
        '#default_value' => $completer,
        '#options' => $traders ? array(0 => t('(Please choose...)')) + $traders : NULL,
        '#required' => TRUE,
        '#element_validate' => array('cc_validate_2parties'),
      );
    }
    
    if (count($allowed_transactions) > 1 && ($edit_all_transactions || $ttype == '')) {
      $form['transaction_type'] = array(
        '#type' => 'select',
        '#title' => t('Transaction type'),
        '#default_value' => $ttype,
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
    $form['#theme'] = array('transaction_starter_form');
  }
  
  if ($edit_all_transactions &&
      $mode == 'init' &&
      $selectorset == 'starter_completer' &&
      ($allowed_transactions['incoming_confirm'] || $allowed_transactions['outgoing_confirm'])) {
    $form['state'] = array(
      '#type' => 'checkbox',
      '#title' => t('Tick to request confirmation'),
      '#default_value' => $transaction->state,
    );
  }
  else {
    $form['state'] = array(
      '#type' => 'hidden',
      '#default_value' => $transaction->state,
    );
  }
  
  $form['quantity'] = array(
    '#type' => 'textfield',
    '#title' => t('Quantity'),
    '#default_value' => intval($transaction->quantity),
    '#field_suffix' => $default_currency->title,
    '#element_validate' => array('cc_validate_quantity'),
    '#maxlength' => 7,
    '#required' => TRUE,
  );
  
  $form['division'] = array(
    '#type' => 'value',
    '#value' => 0,
  );
 //this is begging to be ajax because we don't know what the division is until we know the currency
  if ($default_currency->division == 'sixtieths') {
    $form['division'] = array(
      '#type' => 'select',
      '#title' => t('Hours'),
      '#weight' => $form['quantity']['#weight']+1,
      '#default_value' => 100*($transaction->quantity - intval($transaction->quantity)),
      '#field_suffix' => $default_currency->title,
      '#options' => array(
        '0' => t('00 hours'), 
        '25' => t('15 minutes'), 
        '50' => t('30 minutes'), 
        '75' => t('45 minutes')
      ),
    );
    //these affect the quantity field:
    unset($form['quantity']['#field_suffix']);
    $form['quantity']['#required'] == FALSE;
  }
  
  //the payer has to grade the transaction
  $qualities=variable_get('cc_transaction_qualities', array());
  if (count($qualities) && (($mode != 'init' && $user->uid == $payer) || $mode == 'fulledit')) {
    $form['quality']=array(
      '#type' => 'select',
      '#title' => t('Rate the quality of what was paid for'),
      '#default_value' => intval($transaction->quality),
      '#options' => $qualities,
      '#required' => TRUE,
    );
  }
 
  //the user name selectors need to be either dropdowns or autocomletes
  if ($user_select_widget == 'textfield') {
    foreach (array('starter_uid', 'completer_uid', 'payer_uid', 'payee_uid') as $fieldname) {
      if ($form[$fieldname]['#type'] == 'textfield') {
        $form[$fieldname]['#description'] = t('Please type a username (or user ID)');
        $form[$fieldname]['#autocomplete_path'] = 'user/autocomplete';
        if ($form[$fieldname]['#default_value']) {
          $form[$fieldname]['#default_value'] = db_result(db_query("SELECT name FROM {users} WHERE uid = '%d'", $form[$fieldname]['#default_value']));
        }
      }
    }
  }
  if ($mode == 'summary') {
    foreach (array('title', 'quantity', 'division') as $field) {
      $form[$field]['#type'] = 'hidden';
    }
  }
  return $form;
}

/*
 * I needed a multistep alternative to node/add/transaction form, which creates the transaction using the transactions api, not via Drual
 * Sorry to say it, but Drupal nodes just weren't flexible enough
 */
function transaction_create_form($form_state, $transaction = NULL, $selectorset = 'starter_completer', $presets = 'editable') {
  //this is a nasty hack but it's problematic trying to pass initial variables to a multistep form
  //The function seems to be run for every form on the page, or at least by devel!
  //on form submission every form on the page is built using the post, I think
  if (!$form_state['storage']['step'] && ($form_state['post'] == array() || $form_state['post']['transaction_type'])) {
    $form_state['storage']['step'] = 1; //this makes the form reload from POST
  }
  if (count($form_state['values']) || $form_state['storage']['step'] > 1) {
    $transaction  = (object)$form_state['values'];
  }
  switch ($form_state['storage']['step']) {
    case 1: 
      static $form;
      if ($form) return $form; //because the transaction is built again using drupal_rebuild_form. Form API is complex!
      if (!$transaction)$transaction = (object)array();
      if ($selectorset == 'starter_completer' && !$transaction->starter_uid) {
        $transaction->starter_uid = $GLOBALS['user']->uid; 
      }
      elseif ($selectorset == 'payer_payee') {
        $transaction->transaction_type = '3rdparty_direct';
        $form['state']['#description'] = t('Payer wil be asked to confirm');
      }
      $mode = $form_state['clicked_button']['#id'] == 'edit-previous' ? $mode = 'edit' : $mode = 'init';
      $form = transaction_base_form($transaction, $mode, $selectorset);
      if (arg(0) != 'transaction') $form['#action'] = url('transaction');
      $form['quality']['#access'] = FALSE;
      $form['state']['#default_value'] = $selectorset == 'starter_completer' ? TRANSACTION_STATE_PENDING : TRANSACTION_STATE_COMPLETED;
      //By pretending to be the transaction node form, we get access to the to the node hooks (and to taxonomy_form_alter)
      //not sure we need any of these actually, Is this what gives us nodeapi access as well?
      $form['type'] = array('#value' => 'transaction', '#type' => 'value');
      $form['#node'] = (object)array('type' => 'transaction');
      //what to do with the preset fields
      switch ($presets) {
        case 'disabled':
          foreach ((array)$transaction as $prop => $v) {
            $form[$prop]['#disabled'] = TRUE;
          }
        case 'hidden':
          foreach ((array)$transaction as $prop => $v) {
            $form[$prop]['#access'] = FALSE;
          }
      }
      break;
    case 2:
      drupal_set_title(t('Do you want to start this transaction?'));
      $form = transaction_base_form($transaction, 'summary', $selectorset);
      $form['#summary'] = node_view($transaction, TRUE, FALSE, FALSE);
      $form['previous'] = array(
        '#type' => 'submit',
        '#value' => t('Go back'),
        '#weight' => 11,
      );
      $form['#redirect'] = 'user/'. $transaction->starter_uid;
      break;
  }
  $form['next'] = array(
    '#type' => 'submit',
    '#value' => t('Send'),
    '#weight' => 10
  );

  $form['#validate'][] = 'transaction_create_form_validate';
  return $form;
}


//this fills in the gaps in the transaction form
//It's here because the inc file is not included at this stage of form processing
function transaction_form_after_build($form, &$form_state) {
  //this runs every time a page loads containing the form. We don't want it throwing up errors if it hasn't been submitted
  if (!count($form['#post'])) return $form;
  if ($form_state['values']['starter_uid'] && $form_state['values']['completer_uid']) {
    cc_validate_trader('completer_uid', $form_state['values']['completer_uid']);
    cc_validate_trader('starter_uid', $form_state['values']['starter_uid']);
    //you cannot have a a transaction without payer and payee
    if (substr($form_state['values']['transaction_type'], 0, 8) == 'incoming') {
      //this is for flows towards the starter user
      $form_state['values']['payer_uid']  = $form_state['values']['completer_uid'];
      $form_state['values']['payee_uid'] = $form_state['values']['starter_uid'];
    }
    elseif (substr($form_state['values']['transaction_type'], 0, 8) == 'outgoing') {
      //flows away from the starter user
      $form_state['values']['payer_uid'] = $form_state['values']['starter_uid'];
      $form_state['values']['payee_uid'] = $form_state['values']['completer_uid'];
    }
  }
  elseif ($form_state['values']['payer_uid'] && $form_state['values']['payee_uid']) {
    cc_validate_trader('payee_uid', $form_state['values']['payee_uid']);
    cc_validate_trader('payer_uid', $form_state['values']['payer_uid']);
    //however we only need to know the starter/completer if the transaction is unconfirmed
    if (substr($form_state['values']['transaction_type'], 0, 8) == 'outgoing') {
      //this is for flows towards the starter user
      $form_state['values']['starter_uid'] = $form_state['values']['payee_uid'];
      $form_state['values']['completer_uid'] = $form_state['values']['payer_uid'];
    }
    elseif (substr($form_state['values']['transaction_type'], 0, 8) == 'incoming') {
      //flows away from the starter user
      $form_state['values']['starter_uid'] = $form_state['values']['payer_uid'];
      $form_state['values']['completer_uid'] = $form_state['values']['payee_uid'];
    }
    else {
      //in most cases there won't even be a transaction type for 3rdparty
      //It doesn't matter who the starter and completer are, but the transaction can't be pendinng
      $form_state['values']['state'] = TRANSACTION_STATE_COMPLETED;
    }
  }
  //put the quantity and division fields into one decimal value
  $form_state['values']['quantity'] = intval($form_state['values']['quantity']) + $form_state['values']['division']/100;
  
  //any direct transactims can never be in a pending state
  if (substr($form_state['values']['transaction_type'], -7) == '_direct') {
    $form_state['values']['state'] = TRANSACTION_STATE_COMPLETED;
  }
  return $form;
}

function transaction_create_form_validate($form, &$form_state) {
  //this is what calls nodeapi with $op = 'validate'
  node_validate($form_state['values'], $form);
}

function transaction_create_form_submit($form, &$form_state) {
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
    $options = $form_state['values'];
    $node = generate_transaction_node($options['title'], $options['payer_uid'], $options['payee_uid'], $options['quantity'], $options, $options['cid']);
    //otherwise #redirect doesn't work
    unset($form_state['storage']);
  }
}

function transaction_complete_form(&$form_state, $transaction) {
  $form = array();
  $form['nid'] = array(
    '#type' => 'value',
    '#value' => $transaction->nid,
  );
  $form['#redirect'] = 'user/'. $transaction->completer_uid;
  $output = confirm_form($form,
    t('Complete the transaction?'),
    'user/'. $transaction->completer_uid,
    t('Are you sure you want to complete this transaction?') . theme('node', $transaction, TRUE, FALSE),
    t('Complete'), t('Cancel'),
    'complete'
  );
  return $output;
}

function transaction_complete_form_submit($form, &$form_state) {
  module_load_include('admin.inc', 'transactions');
  if ($form_state['values']['complete']) {
    $transaction = node_load($form_state['values']['nid']);
    db_query(
      'UPDATE {cc_transactions} SET state = %d WHERE nid = %d',
      array(
        ':state' => TRANSACTION_STATE_COMPLETED,
        ':nid' => $transaction->nid
      )
    );
    recalculate_balances($transaction->payer_uid, $transaction->cid);
    recalculate_balances($transaction->payee_uid, $transaction->cid);
    stats_refresh($trsnsaction->cid);
    drupal_set_message(t('Transaction with !starter completed', array('!starter' => theme('username', user_load($transaction->starter_uid)))));
  }
}

function cc_validate_2parties($element, $form_state) {
  if (($form_state['values']['payer_uid'] == $form_state['values']['payee_uid'] && $form_state['values']['payee_uid'] != 0 )
  || ($form_state['values']['starter_uid'] == $form_state['values']['completer_uid']  && $form_state['values']['completer_uid'] != 0 ) ) {
    form_error($element, t('A transaction must involve two different members'));
  }
}
function cc_validate_completer($element, $form_state) {
  if ($form_state['values']['starter_uid'] && !$element['#value']) {
    form_error($element, t('Who are you transacting with?'));
  }
}

function cc_validate_title($element, $form_state) {
  if (!check_plain($element['#value'])) {
    form_error($element, t('Plain text only in transaction descriptions.'));
  }
  if (str_word_count($element['#value']) < variable_get('cc_description_min_words', 4)) {
    form_error($element, t('Please describe the transaction in more detail'));
  }
}

//by the time this this runs, the after_build function has added the division to the quantity
function cc_validate_quantity($element, $form_state) {
  $quant = $element['#value'];
  if (strlen($quant)) {
    if ($quant < 0) {
      form_error($element, t('Negative numbers not allowed. You may be able to change the direction of the transaction'));
    }
    if (intval($quant) != (float)$quant) {
      form_error($element, t('You must enter a whole number'));
    }
  }
  else {
    form_error($element, t('You cannot exchange nothing'));
  }
  /*
  if (isset($form_state['values']['division'])) {
    $division = $form_state['values']['division'];
    $currency = currency_load($transaction->cid);
    switch ($currency->division) {
      case 'minutes':
        if ($division > 60) {
          form_set_error('division', t('Enter a number less than 60'));
        }
      case 'decimal':
        if (is_numeric($division)) {
          if (intval($division) != (float)$division) {
            form_set_error('division', t('You must enter a whole number'));
          }
          elseif ($division < 0) {
            form_set_error('division', t('Negative numbers not allowed. You may be able to change the direction of the transaction'));
          }
        }
    }
  }
  if ((!is_numeric($quant) || $quant == 0) && (!is_numeric($division) || $division == 0)){
    form_error($element, t('You must enter a number'));
  }
  */
}


/*
 * email notification
*/
function transactions_email_starter_action($node, $context = array()) {
  //load the user and continue only if the completer wants notification
  $recipient = user_load($node->starter_uid);
  if ($recipient->starter_notification == FALSE) return;
  drupal_mail(
    'transactions',
    'transaction_starter',
    $recipient->mail,
    user_preferred_language($recipient),
    array(
      'transaction' => $node, 
      'template' => $context['template'],
      'subject' => $context['subject'],
    )
  );
  drupal_set_message(t('!user has been notified by email.', array('!user' => theme('username', $recipient))));
}
function transactions_email_completer_action($node, $context = array()) {
  if ($node->type != 'transaction') return;
  //load the user and continue only if the completer wants notification
  $recipient = user_load($node->completer_uid);
  if ($recipient->completer_notification == FALSE) return;
  drupal_mail(
    'transactions',
    'transaction_completer',
    $recipient->mail,
    user_preferred_language($recipient),
    array( //this is the $params in transactions_mail
      'transaction' => $node, 
      'template' => $context['template'],
      'subject' => $context['subject'],
    )
  );
  drupal_set_message(t('!user has been notified by email.', array('!user' => theme('username', $recipient))));
}

//generates an email message telling the user that that they have a pending transaction to complete
//params needs to be array($transaction)
//TODO make this mime_mail aware
function transactions_mail($key, &$message, $params) {
  $starter = user_load($params['transaction']->starter_uid);
  $completer = user_load($params['transaction']->completer_uid);
  switch ($key) {
    case 'transaction_starter':
      $recipient = $starter;
      break;
    case 'transaction_completer':
      $recipient = $completer;
      break;
  }
  $variables = array(
    '%completer' => $completer->name,
    '%starter' => $starter->name,
    '%summary' => node_view($params['transaction'], TRUE, FALSE, FALSE),
    '%reason' => $params['transaction']->title,
    '%profile_url' =>  url('user/'. $recipient->uid, array('absolute' => TRUE)),
    '%pending_url' => url('user/'. $recipient->uid .'/pending', array('absolute' => TRUE)),
    '%bureau_url' => url('user/'. $recipient->uid .'/bureau', array('absolute' => TRUE)),
    '%unsubscribe_url' => url('user/'. $recipient->uid .'/edit/account', array('fragment' => 'notifications', 'absolute' => TRUE))
  );
  if (module_exists('mimemail')) {
    //the following tokens require mime_mail to be installed
    $variables['%statement'] = l(t('statement'), 'user/' . $recipient->uid . '/statement', array('absolute' => TRUE));
    $variables['%site_name'] = l(variable_get('site_name', 'web site'), 'user/'.$recipient->uid, array('absolute' => TRUE));
    //we link to 'edit/account' here because usertabs is often installed, and it will fall back to 'edit' anyway
    $variables['%unsubscribe'] = l(t('Unsubscribe'), 'user/' . $recipient->uid . '/edit/account', array('fragment' => 'notifications', 'absolute' =>TRUE));
    //TODO make the below work. Currently, $transaction is being passed without the nid because it is only just created
    //'%transaction' => l(t('transaction'), 'node/' . $transaction->nid . '/edit', array('absolute' =>TRUE)),
  }
  else {
    $variables['%summary'] = strip_tags($variables['%summary']);
  }
 
  $message['body'][] = strtr($params['template'], $variables);
  $message['subject'] = $message['params']['subject'];
}


/*
 * PREPROCESSING FUNCTIONS
*/
//balance history chart
//$options (not very tested) are array(
//  currencies => an array of currency objects, defaults to all
//  states => an array of states, defaults to all
//  first_time => linux era seconds of start of chart.
//  )
function template_preprocess_balance_history(&$vars) {
  //determine which transaction states wil be used to draw the line
  $default_states = array(TRANSACTION_STATE_COMPLETED);
  if (variable_get('cc_count_pending', FALSE)) {
    $default_states[] = TRANSACTION_STATE_PENDING;
  }
  $vars['currencies'] = $vars['options']['currencies']
    or $vars['currencies'] = currencies_load(array('uids' => array($vars['account']->uid)));
  $states = $vars['options']['states'] 
    or $states = $vars['options']['currencies'];
  $vars['first_time'] = $vars['options']['first_time'] 
    or $vars['first_time'] = $vars['account']->created;
  unset($vars['options']);
  
  //make an array of points
  $lines=array();
  $line_styles=array();
  $line_colors=array();
  foreach ($vars['currencies'] as $currency) {
    $cid  = $currency->cid;
    $vars['points'][$cid] = array();
    //gets all completed transactions with a running balance
    $transactions = _get_transactions_for_user(
      $vars['account']->uid,
      array(
        'states' => $states,
        'running_balance' => TRUE,
        'cid' => $currency->cid
      )
    );
    
    if (count($transactions)) {
      $vars['points'][$cid][$first_time] = 0;
      //add all the other points, one per transaction
      foreach ($transactions as $transaction) {
        //one point per transaction produces diagaonal lines
        $vars['points'][$cid][$transaction->{created}] = $transaction->balance;
      }
      //add a point showing the balance constant from the last transaction until now
      $vars['points'][$cid][time()] = $transaction->balance;
    }
    $vars['maxes'][$cid] = $vars['account']->balances[$cid]['max_balance'];
    $vars['mins'][$cid] = $vars['account']->balances[$cid]['min_balance'];
  }
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
  $vars['volumes'][0][$cid]['income'] = floatval($vars['account']->balances[$cid]['gross_income']);
  $vars['volumes'][0][$cid]['expenditure'] = floatval($vars['account']->balances[$cid]['gross_expenditure']);
  
  //then add values from each stats period and currency
  $states = array(TRANSACTION_STATE_COMPLETED);
  if (variable_get('cc_count_pending', FALSE)) {
    $states[] = TRANSACTION_STATE_PENDING;
  }
  
  foreach (variable_get('cc_stats_periods', array("1 month", "1 year")) as $period) {
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

//defaults options see below
function template_preprocess_balance_limits(&$vars) {
  foreach(array_keys($vars['account']->balances) as $cid) {
    $vars['min'][$cid] = $vars['account']->balances[$cid]['min_balance'];
    $vars['max'][$cid] = $vars['account']->balances[$cid]['max_balance'];
    $vars['balance'][$cid] = floatval($vars['account']->balances[$cid]['cleared_balance']);
    $vars['currencies'][$cid] = currency_load($cid);
  }
}


//takes the transaction node opbject and returns a load of ready to print fields, in an array
function preprocess_transaction_fields($transaction, $uid) {
  $props = array('node' => $transaction);
  $classes = array();
  //this is for the initial confirmation form, before the transaction is saved to db
  if (!$transaction->created) {
    $transaction->created = time();
  }
  $props['submitted'] = format_date($transaction->created, 'short');
  $props['description'] = $transaction->title;
  $props['payer'] = theme('username', user_load($transaction->payer_uid));
  $props['payee'] = theme('username', user_load($transaction->payee_uid));
  $props['amount'] = theme('money', $transaction->quantity, $transaction->cid);
  $props['balance'] = theme('money', $transaction->balance, $transaction->cid);
  $props['classes'][] = $transaction->state ? 'pending' : 'completed';

  if ($transaction->starter_uid) {
    $props['starter'] = theme('username', user_load($transaction->starter_uid));
  }
  if ($transaction->completer_uid) {
    $props['completer'] = theme('username', user_load($transaction->completer_uid));
  }
  if (count($qualities = variable_get('cc_transaction_qualities', array()))) {
    $classes[] = 'quality-'. $transaction->quality;
    $classes[] = $qualities[$transaction->quality];
  }
  if ($transaction->payer_uid == $uid) {
    $props['notme'] = $props['payee'];
  }
  elseif ($transaction->payee_uid == $uid) {
    $props['notme'] = $props['payer'];
  }
  if ($transaction->nid) {
    $props['transaction_link'] = l($transaction->title, 'node/'. $transaction->nid);
  }
  else {
    $props['transaction_link'] = $transaction->title;
  }
  return $props;
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



function template_preprocess_transaction_starter_form(&$vars) {
  template_preprocess_transaction_form($vars);
}
function template_preprocess_transaction_3rdparty_form(&$vars) {
  template_preprocess_transaction_form($vars);
}

function template_preprocess_transaction_form(&$vars) {
  $vars['mode'] = $vars['form']['#mode'];
  $vars['selector_set'] = $vars['form']['#selector_set'];
  foreach ($vars['form'] as $key => $field) {
    if (strpos($key, '#') !== 0 && is_array($field)  && $key != 'form_token' ) {
      if ($field['#type'] && $field['#type'] != 'hidden') {
        if ($field['#type'] != 'checkbox') {
          $vars['form'][$key]['#title'] = NULL;
          $vars['form'][$key]['#description'] = NULL;
        }
        $vars[$key] = drupal_render($vars['form'][$key]);
      }
    }
  }
  $vars['hidden_fields'] = drupal_render($vars['form']);
  $vars['summary'] = $vars['form']['#summary'];
}



function show_stats($timestring, $theme = 'page', $cid = 0) {
  $cached = cache_get(str_replace(' ', '-', $timestring) .'-'. $cid);
  return theme('stats_'. $theme, $cached->data);
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
  $html = '<div class="stat">'. t("Number of transactions: @num", array('@num' => $stats['misc']['transaction_count'])) .'</div>';
  $html .= '<div class="stat">'. t("Total volume traded: @num", array('@num' => array_sum($stats['highest_incomes']))) .'</div>';
  $html .= '<div class="stat">'. t("Number of trading members: @num", array('@num' => $stats['misc']['active_members'])) .'</div>';
  //shorten the arrays to a useful amount
  foreach ($stats['trades_per_user'] as $uid => $count) {
    $best_traders[] = theme('username', user_load($uid)) .' ('. $count .')';
    if (count($best_traders) == $top_how_many) break;
  }
  $html .= '<div class="stat">'. t("Most trades:") .'<ol><li>'. implode("</li>\n<li>", $best_traders) .'</li></ol></div>';
  foreach ($stats['highest_incomes'] as $uid => $count) {
    $incomes[] =  theme('username', user_load($uid)) .' ('. $count .')';
    if (count($incomes) == $top_how_many) break;
  }
  $html .= '<div class="stat">'. t("Largest incomes:") .'<ol><li>'. implode("</li>\n<li>", $incomes) .'</li></ol></div>';
  foreach ($stats['highest_expenditures'] as $uid => $count) {
    $expenditure[] =  theme('username', user_load($uid)) .' ('. $count .')';
    if (count($expenditure) == $top_how_many) break;
  }
  $html .= '<div class="stat">'. t("Largest expenditure:") .'<ol><li>'. implode("</li>\n<li>", $expenditure) .'</li></ol></div>';
  
  foreach ($stats['highest_volumes'] as $uid => $count) {
    $volumes[] =  theme('username', user_load($uid)) .' ('. $count .')';
    if (count($volumes) == $top_how_many) break;
  }
  $html .= '<div class="stat">'. t("Highest trading volumes:") .'<ol><li>'. implode("</li>\n<li>", $volumes) .'</li></ol></div>';
  return '<div class="stats">'. $html .'</div>';
}

function theme_rating($quality, $currency) {
  return $quality;
}


/*
 * Gets all the transactions (up to a time or a count)
 * optionally calculates the running balance,
 * and returning an array of transactions, keyed with nids
 *
 * Options are (showing defaults) array(
 *   'states' => array(TRANSACTION_STATE_COMPLETED),
 *   'until' => time() +1,
 *   'cid' => all,
 *   'direction' => both, (can be in or out)
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
  //time limits
  if ($until) {
    $filters[] = " n.created < $until ";
  }
  else {
    //ensures we include transactions created this very query
    $until = time() + 1;
  }
  
  if ($cid) {
    $filters[] = " t.cid = $cid ";
  }
  $query = "SELECT n.uid, n.title, n.nid, t.payer_uid, t.payee_uid, t.starter_uid, t.completer_uid, t.cid, t.quantity, t.quality, t.state, n.created, t.transaction_type
      FROM {node} n
      LEFT JOIN {cc_transactions} t ON t.nid = n.nid
      WHERE n.type = 'transaction'
      AND %s
      ORDER BY n.created ASC
    ";
  $args = array(implode(' AND ', $filters));
  $results = db_query($query, $args);
  
  //gets all the transactions, calculates the running balances and discards transactions before the time limit
  if ($running_balance) {
    $running_balance = array(); //one balance for each currency (not used)
    $currencies = currencies_load(array('uids' => array($uid)));
    foreach ($currencies as $cid => $currency) {
      $running_balance[$cid] = 0;
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
    $transactions[$nid] = $transaction;
  }
  return $transactions;
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
 * limit checker
 * checks an account to see if the proposed difference will violate the balance limits
 */

function _transaction_user_limits($account, $difference, $cid = 0) {
  $messages=array();
  if ((float) $difference > 0) {
    $credit = $account->balances[$cid]['cleared_balance'] + $difference - $account->balances[$cid]['max_balance'];
    if ($credit > 0) {
      $message = t('Transaction would take @username !dif above the maximum balance of !quantity', 
        array(
          '@username' => strip_tags(theme('username', $account)),
          '!dif' => theme('money', $credit, $cid),
          '!quantity' => theme('money', $account->balances[$cid]['max_balance'], $cid)
        )
      );
    }
  }
  else {
    $debit = $account->balances[$cid]['min_balance'] - ($account->balances[$cid]['cleared_balance'] + $difference);
    if ($debit > 0) {
      $message = t('Transaction would take @username !dif below the minimum balance of !quantity', 
        array(
          '@username' => strip_tags(theme('username', $account)),
          '!dif' => theme('money', $debit, $cid),
          '!quantity' => theme('money', $account->balances[$cid]['min_balance'], $cid)
        )
      );
    }
  }
  if ($message) {
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
/**
 * http://api.drupal.org/api/function/hook_link/6
 * Add the 'operations' links to each transaction node, according to logged in user permissions
 */
function transactions_link($type, $transaction) {
  if ($type != 'node' || !$transaction->nid) return array();
  global $user;

  if (transactions_access('complete', NULL, $user->uid, $transaction) && $transaction->state == TRANSACTION_STATE_PENDING) {
    $links['complete'] = array(
      'title' => t('Sign'),
      'href' => 'transaction/'. $transaction->nid .'/complete',
    );
  }
  if (transactions_access('edit', NULL, $user->uid, $transaction)) {
    $links['change'] = array(
      'title' => t('Change'),
      'href' => 'node/'. $transaction->nid .'/edit',
    );
  }
  //delete button
  if (transactions_access('delete', NULL, $user->uid, $transaction)) {
    $links['erase'] = array(
      'title' => t('Erase'),
      'href' => 'node/'. $transaction->nid .'/delete',
    );
  }
  return $links;
}

//this identifies users using incoming uids, names, emails validates they exist as traders, and returns the uid
//it is not a field specific validation callback, but is called by transaction_form_after_build
function cc_validate_trader($form_field_key, &$value) {
  $type = gettype($value);
  switch ($type) {
    case 'array':
      foreach ($value as $key => $one_value) { 
        cc_validate_trader($form_field_key, $one_value);
        $value[$key] = $one_value;
      }
      return;
    case 'integer':
      $field = 'uid';
      break;
    case 'string':
      if (is_numeric($value)) {
         $field = 'uid';
      }
      elseif (valid_email_address($value)) {
        $field = 'mail';
      }
      elseif (is_string($value)) {
        $field = 'name';
      }
  }
  $criterion = array($field => $value);
  $account = user_load($criterion);
  if ($account) {
    $value = $account->uid;
    if (!user_access('use local currency', $account) || !$account->status) {
      form_set_error($form_field_key, t("@type @fieldname: @data is not permitted to trade", array("@type" => $type, "@fieldname" => $form_field_key, "@data" => $value)));
    }
  }
  else {
    form_set_error($form_field_key, t("@type @fieldname: @data is not a known user", array("@type" => $type, "@fieldname" => $form_field_key, "@data" => $value)));
  }
}

//determines whether an account which doesn't now have permission to trade, used to.
function visible_balances($account) {
  if (user_access('use local currency', $account)) return TRUE;
  foreach ($account->balances as $cid => $balances) {
    if ($balances->gross_income || $balances->gross_expenditure || $balances->pending_balance) return TRUE;
  }
}