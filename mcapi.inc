<?php
// $Id: mcapi.inc,v 1.1.2.7 2010/12/22 19:30:29 matslats Exp $

/*
 * this is called by the hook mcapi_entity_info
 */
function mcapi_entity_info_inc() {
  $entities['transaction'] = array(
    'label' => t('Transaction'),
    'controller class' => variable_get('mcapi_controller', 'mcapi') .'_EntityController',
    'base table' => 'mcapi_transactions',
    'uri callback' => 'transaction_uri', //what is this for???
    'label callback' => 'transaction_label',
    'access callback' => 'transaction_access',
    'fieldable' => TRUE,
    'entity keys' => array(
      'id' => 'xid',
    ),
    'bundles' => array(
      'transaction' => array(
        'label' => t('Transaction'),
        'admin' => array(
          //not sure what the difference is between these
          'path' => 'admin/accounting',
          'real path' => 'admin/accounting',
          'access arguments' => array('manage all transactions'),
        )
      ),
    ),
    'load hook' => 'mcapi_xaction_load',//this avoids conflict with commerce module
    'view callback' => 'transaction_view', //used by entity module
    'view modes' => array(
      'certificate' => array(
        'label' => t('Certificate'),
        'custom settings' => FALSE,
      ),
      'sentence' => array(
        'label' => t('sentence'),
        'custom settings' => FALSE,
      ),
    )
  );
  return $entities;
}

function mcapi_menu_inc() {
  $items['admin/accounting'] = array(
    'title' => 'Accounting',
    'description' => 'Member to member transactions',
    'page callback' => 'mcapi_accounting',
    'access arguments' => array('manage all transactions'),
    'file' => 'mcapi.inc',
    'weight' => 2
  );
  $items['admin/accounting/record'] = array(
    'title' => 'Record transaction',
    'description' => 'Set all the properties manually on a new transaction',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('transaction_admin_form'),
    'access arguments' => array('manage all transactions'),
    'file' => 'mcapi.inc',
  );
  $items['admin/accounting/entity_controller'] = array(
    'title' => 'Entity controller',
    'description' => "Choose which lump of code reads and writes your transaction data.",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_controller_options_form'),
    'access arguments' => array('manage all transactions'),
    'file' => 'mcapi.install',
  );
  $items['admin/accounting/currencies/%currency/edit'] = array(
    'title' => 'Modify currency',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('currency_form', 3),
    'access callback' => 'currency_edit_access',
    'access arguments' => array(3),
    'type' => MENU_CALLBACK,
    'file' => 'currencies.inc'
  );
  $items['transaction/%transaction'] = array(
    'title callback' => 'transaction_menu_title',
    'title arguments' => array(1),
    'title' => 'Transaction certificate',
    // The page callback also invokes drupal_set_title() in case
    // the menu router's title is overridden by a menu link.
    'page callback' => 'transaction_view',
    'page arguments' => array(1),
    'access callback' => 'transaction_access',
    'access arguments' => array('view', 1),
    'file' => 'mcapi.inc',
  );
  $items['transaction/%transaction/view'] = array(
    'title' => 'View',
    'page callback' => 'transaction_view',
    'access callback' => 'transaction_access',
    'access arguments' => array('view', 1),
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
    'file' => 'mcapi.inc',
  );
  $items['transaction/%transaction/edit'] = array(
    'title' => 'Admin edit',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('transaction_admin_form', 1),
    'access callback' => 'transaction_adminedit',
    'access arguments' => array(1),
    'type' => MENU_LOCAL_TASK,
    'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
    'file' => 'mcapi.inc',
    'weight' => 1
  );
  $items['transaction/%transaction/erase'] = array(
    'title' => 'Erase transaction',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_transaction_erase_confirm_form', 1),
    'access callback' => 'transaction_access',
    'access arguments' => array('erase', 1),
    'weight' => 1,
    'type' => MENU_LOCAL_ACTION,
    'file' => 'mcapi.inc',
  );
  return $items;
}

/*
 * menu_callback top level config page for mcapi
 */
function mcapi_accounting() {
  module_load_include('admin.inc', 'system');
  return system_admin_menu_block_page();
}

function transaction_menu_title($transaction) {
  return t('Transaction certificate #@serial', array('@serial' => $transaction->serial));
}

/**
 * All transaction forms should use this function as a basis using hook_forms
 */
function _transaction_form($form, &$form_state, $transaction) {
  $form['serial'] = array(
    '#type'=> 'hidden',
    '#value' => isset($transaction->serial) ? $transaction->serial : 0
  );
  $form['payer'] = array(
    '#type' => 'textfield',
    '#title' => t('Account number to be debited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => $transaction->payer,
    '#element_validate' => array('mcapi_validate_usernames', 'mcapi_validate_transactor'),
    '#autocomplete_path' => 'user/autocomplete',
    '#weight' => 6,
  );
  $form['payee'] = array(
    '#type' => 'textfield',
    '#title' => t('Account number to be credited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => $transaction->payee,
    '#element_validate' => array('mcapi_validate_usernames', 'mcapi_validate_transactor'),
    '#autocomplete_path' => 'user/autocomplete',
    '#weight' => 9,
  );
  $types = module_invoke_all('mcapi_info_types');
  unset($types[array_search('unspecified', $types)]);
  $form['type'] = array(
    '#title' => t('Transaction type'),
    '#options' => drupal_map_assoc($types),
    '#type' => 'select',
    '#default_value' => $transaction->type ? $transaction->type : 'auto',
    '#element_validate' => array('mcapi_validate_ttype'),
    '#required' => TRUE,
    '#weight' => 15
  );
  $form['state'] = array(
    '#type' => 'radios',
    '#title' => t('State'),
    '#description' => mcapi_get_states('#description'),
    '#default_value' => is_null($transaction->state) ? TRANSACTION_STATE_FINISHED : $transaction->state,
    '#options' => mcapi_get_states('#options'),
    '#element_validate' => array('mcapi_validate_state'),
    '#weight' => 18
  );
  $form['creator'] = array(
    '#type' => 'textfield',
    '#title' => t('Recorded by'),
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => empty($form_state['values']['creator']) ? user_load($transaction->creator)->name : user_load($form_state['values']['creator'])->name,
    '#element_validate' => array('mcapi_validate_usernames'),
    '#required' => TRUE,
    '#weight' => 20,
  );

  //TODO
  //I'm just fed up of multistep forms
  //the following doesn't retrieve default values out of form_state when we come back from step 2
  try {
    field_attach_form('transaction', $transaction, $form, $form_state);
  }
  catch (Exception $e) {
    drupal_set_message(t('Cannot build transaction form:').' '. $e->getMessage(), 'error');
    return array();
  }
  $lang = current(element_children($form['worth'])); //assumes only one language
  unset($form['worth'][$lang]['add_more']);
  
  if (@$form_state['build_info']['args'][TRANSACTION_BUILD_INFO_ARG]->state == TRANSACTION_STATE_FINISHED) {
    $form['buttons']['update'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
      '#access' => transaction_access('update', $transaction)
    );
    $form['buttons']['erase']=array(
      '#theme' => 'link',
      '#path' => 'transaction/'. $transaction->serial .'/erase',
      '#text' => t('Erase'),
      '#access' => transaction_access('erase', $transaction),
      '#options' => array(
        'attributes' => array(),
        'html' => FALSE
      ),
    );
  }
  else {
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Record'),
    );
  }
  $form['buttons']['#weight'] = 25;
  $form['#validate'] = array('transaction_first_form_validate');
  $form['#submit'] = array('transaction_form_submit');
  $form_state['redirect'] = 'user';
  return $form;
}

/*
 * standard form-level validation callback for ALL transaction forms
 * fields have already been validated individually
 * By this time each of the form values should be well enough validated not to fail on writing to db
 *
 */
function transaction_first_form_validate($form, &$form_state) {
  form_state_values_clean($form_state);
  $transaction = (object)$form_state['values'];
  //populate the core transaction values from $form_state
  entity_form_submit_build_entity('transaction', $transaction, $form, $form_state);
  //validate the Field API fields
  field_attach_form_validate('transaction', $transaction, $form, $form_state);
  if (count(form_get_errors())) return;
  //validate the transaction qua transaction
  transaction_validate($transaction, TRUE);
}

/*
 * form submit callback from the base transaction form, hence used by all forms
 * hence must be in .module file
 * this is what actually saves the data
 */
function transaction_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);
  $transaction = (object)$form_state['values'];
  $op = isset($transaction->serial) ? 'update' : 'insert';
  entity_form_submit_build_entity('transaction', $transaction, $form, $form_state);
  transaction_save($transaction);
  $func = 'field_attach_'.$op;
  $func('transaction', $transaction);

  if (!isset($form_state['redirect'])) {
    $form_state['redirect'] = 'transaction/'.$transaction->serial;
  }
  $form_state['values']['serial'] = $transaction->serial;
}


/*
 * implements hook_transaction_validate
 * Some wiggle room has been left in case for any reason we want to store transactions with missing fields
 */
function mcapi_transaction_validate($transaction){
  //no usecase is currently envisaged where someone might pay themselves in one currency in one transaction
  if ($transaction->payer > 0) {
    if ($transaction->payer == $transaction->payee) {
      throw new Exception(t('A transaction must involve two different users'));
    }
  }
  //a transaction can only be complete if all these fields are filled in.
  if ($transaction->state >= TRANSACTION_STATE_FINISHED) {
    $required = array('payer', 'payee',  'type');
    foreach ($required as $fieldname) {
      if (!$transaction->$fieldname) {
        throw new Exception(t("@fieldname required for completed transaction", array('@fieldname' => $fieldname)));
      }
    }
  }
}

/*
 * validate callback for transaction form participants - accepts uid only
 */
function mcapi_validate_transactor(&$element, $form_state) {
  $uid = &$element['#value'];
  if (is_null($uid)) return;
  $account = user_load($uid);
  if (empty($account->uid)) {
    form_error($element, t('User ID does not exist: @user', array('@user' => $uid)));
  }
  elseif(!user_access('transact', $account)) {
    form_error($element, t("User ID is not permitted to 'transact': @user", array('@user' => format_username($account))));
  }
}

/*
 * Confirmation form before deleting a transaction
 * Couldn't be bothered to provide a theme callack for such a rarely used form.
 */
function mcapi_transaction_erase_confirm_form($form, $form_state, $transaction){
  $form['serial'] = array(
    '#type' => 'hidden',
    '#value' => $transaction->serial
  );
  $question = t('Are you sure you want to erase transaction #@serial?', array('@serial' => $transaction->serial));
  $path = 'transaction/'.$transaction->serial;
  $description = t('This transaction was confirmed, so the balances of both users will be affected');
  $yes = t('Delete');
  $no = t('Cancel');
  $name = 'erase_transaction';
  return confirm_form($form, $question, $path, $description, $yes, $no, $name);
}
/*
 * Submit handler for deleting a transaction
 */
function mcapi_transaction_erase_confirm_form_submit($form, $form_state) {
  transaction_erase($form_state['values']['serial']);
  //fieldapi values are deleted whatever the currency delete mode is
  field_attach_delete('transaction', $transaction);
  //this could be done better with 'destination' in the url
  drupal_goto('user');
}

/*
 * form element validation callback functions
 */
function mcapi_validate_title(&$element, $form_state) {
  //don't know the max length of 'small' text field. Will be just truncated
  $form_state['values']['title'] = check_plain($element['#value']);
}

/*
 * Validate callback for transaction form 'state' element
 * This must happen BEFORE transaction_validate is called
 */
function mcapi_validate_state(&$element, &$form_state) {
  $val = &$element['$value'];
  if ($val == TRANSACTION_STATE_UNSAVED) return;
  if (empty($val) && empty($form_state['values']['serial'])) {
    form_set_value($element, TRANSACTION_STATE_UNSAVED, $form_state);
  }
  else{
    $states = mcapi_get_states();
    if (!isset($states[$val])) {
      form_error($element, t('Invalid transaction state: @value.', array('@value' => $val)));
    }
  }
}

/*
 * Validate callback for transaction form 'type' element
 */
function mcapi_validate_ttype(&$element, $form_state) {
  // _element_validate() checks select widgets in the same way
  if (in_array( $element['#value'], module_invoke_all('mcapi_info_types'))) return;
  form_error($element, t('Invalid transaction type.'));
}


function mcapi_access_unrestricted() {
  return TRUE;
}
function mcapi_access_none() {
  return FALSE;
}
function mcapi_access_creator($transaction, $account) {
  return $transaction->creator == $account->uid;
}
function mcapi_access_authenticated($transaction, $account) {
  return $account->uid;
}
function mcapi_access_participants_admin($transaction, $account, $currency) {
  return mcapi_access_participants($transaction, $account) || mcapi_access_admin($transaction, $account, $currency);
}
function mcapi_access_admin($transaction, $account, $currency) {
  return user_access('manage all transactions', $account);
}
function mcapi_access_participants($transaction, $account) {
  return in_array($account->uid, array($transaction->payer, $transaction->payee));
}

function mcapi_membership_transact($currency, $account) {
  return user_access('transact', $account);
}


/*
 * access control wrapper for currency
 */
function currency_access($op, $currcode, $account = NULL) {
  if ($GLOBALS['user']->uid == 1) return TRUE;
  $function = currency_load($currcode)->access[$op];
  return $function($op, $account);
}

/*
 * currency membership callback
 */
function mcapi_can_transact($op, $account = NULL) {
  return user_access('transact', $account);
}
/*
 * currency membership callback
 */
function mcapi_anyone() {
  return TRUE;
}


/*
 * returns a form element for picking a currency
 * if $picker_options is an array of currencies keyed by currcode, it returns a select
 * if $picker_options is NULL it returns a hidden field
 */
function currency_picker_element(array $currcodes, $default = NULL, $multiple = FALSE) {
  if (empty($currcodes)) drupal_set_message(print_r(debug_backtrace(), 1), 'error');
  if (count($currcodes) < 2) {
    return array(
      '#type' => 'hidden',
      '#value' => current($currcodes)
    );
  }
  return array(
    '#title' => t('Currency'),
    '#type' => 'select',
    '#options' =>  _currency_picker_element_options($currcodes),
    '#default_value' => $default,
    '#multiple' => $multiple,
    '#required' => TRUE,
  );
}
//build a key value array of currency codes and currency names
//$currencies is an array of the form returned by currencies_load
//if $currencies is empty, it will assume you mean all currencies
function _currency_picker_element_options(array $currcodes) {
  $currencies = &currencies_load();
  foreach ($currcodes as $currcode) {
    $options[$currcode] = $currencies[$currcode]->human_name;
  }
  return $options;
}
