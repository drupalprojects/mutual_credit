<?php
// $Id: mcapi.inc,v 1.1.2.7 2010/12/22 19:30:29 matslats Exp $

//it is initialised at the moment that this file is included.
//this includes the controller file


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
    'fieldable' => TRUE,
    'translation' => array(
      'locale' => module_exists('i18n_field') //this concerns the body field only, which is untranslatable by default
    ),
    'entity keys' => array(
      'id' => 'xid',
    ),
    'bundles' => array(
      'transaction' => array(
        'label' => t('Transaction'),
        'admin' => array(
          //not sure what the difference is between these
          'path' => 'admin/accounting/transaction',
          'real path' => 'admin/accounting/transaction',
          'access arguments' => array('manage all transactions'),
        )
      ),
    ),
    'view modes' => array(
      'certificate' => array(
        'label' => t('Certificate'),
        'custom settings' => FALSE,
      ),
      'summary' => array(
        'label' => t('Summary'),
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
    //'position' => 'right',
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
  $items['admin/accounting/currency'] = array(
    'title' => 'Currency configuration',
    'description' => 'Name and appearance of the default currency',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_currency_form'),
    'access arguments' => array('manage all transactions'),
    'file' => 'currencies.admin.inc'
  );
  $items['admin/accounting/entity_controller'] = array(
    'title' => 'Entity controller',
    'description' => "Choose which lump of code reads and writes your transaction data.",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_controller_options_form'),
    'access arguments' => array('manage all transactions'),
    'file' => 'mcapi.install',
  );
  $items['transaction/%transaction'] = array(
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
    'access callback' => 'transaction_access',
    'access arguments' => array('update', 1),
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

/**
 * All transaction forms should use this function as a basis using hook_forms
 */
function transaction_form($form, &$form_state, $transaction) {
  $form['xid'] = array(
    '#type'=> 'hidden',
    '#value' => intval($transaction->xid)
  );
  $form['payer'] = array(
    '#type' => 'textfield',
    '#title' => t('Account number to be debited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => $transaction->payer,
    '#element_validate' => array('mcapi_validate_transactor'),
    '#weight' => 6,
  );
  $form['payee'] = array(
    '#type' => 'textfield',
    '#title' => t('Account number to be credited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => $transaction->payee,
    '#element_validate' => array('mcapi_validate_transactor'),
    '#weight' => 9,
  );
  $form['type'] = array(
    '#title' => t('Transaction type'),
    '#options' => drupal_map_assoc(module_invoke_all('mcapi_info', 'types')),
    '#type' => 'select',
    '#default_value' => $transaction->type,
    '#element_validate' => array('mcapi_validate_ttype'),
    '#required' => TRUE,
    '#weight' => 15
  );
  $states = mcapi_transaction_states();
  //perhaps 'unset' doesn't have to be a state?
  unset($states['unset']);
  $form['state'] = array(
    '#type' => 'radios',
    '#title' => t('State'),
    '#description' => t('Finished transactions cannot have empty values.'),
    '#default_value' => $transaction->state,
    '#options' => $states,
    '#element_validate' => array('mcapi_validate_state'),
    '#weight' => 18
  );
  $form['creator'] = array(
    '#type' => 'textfield',
    '#title' => t('Recorded by'),
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => empty($form_state['values']['creator']) ? user_load($transaction->creator)->name : user_load($form_state['values']['creator'])->name,
    '#element_validate' => array('mcapi_validate_username'),
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
  
  //the default theming for multiple value fields is way over the top, so this module provides a cut down theme callback
  if (count(element_children($form['worth']['und'])) > 1) {
    $form['worth']['und']['#theme'] = 'worth_widget';
    //for some reason the add more button is only heavier than the first element
    $form['worth']['und']['add_more']['#weight'] = 6;
  }

  $form['buttons']['#weight'] = 25;

  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Record'),
  );
  if ($transaction->xid) {
    $form['buttons']['submit']['#value'] = t('Update');
  }
  $form['#entity_builders'][] = 'transaction_entity_builder';
  $form['#validate'] = array('transaction_form_validate');
  $form['#submit'] = array('transaction_all_submit');
  $form_state['redirect'] = 'user';
  return $form;
}

/*
 * standard form-level validation callback for ALL transaction forms
 * fields have already been validated individually
 * Anything that validates in this function should work in the database
 * Even if the form wasn't properly designed in all respects.
 * This validation process allows NULL fields.
 * which also means it can validate partially completed forms
 */
function transaction_form_validate($form, &$form_state) {
  if (empty($form_state['#transaction'])) {
    if (!empty($form_state['values']['xid'])) {
      $form_state['#transaction'] = transaction_load($form_state['values']['xid']);
    }
    else {
      $form_state['#transaction'] = new transaction;
    }
  }
  entity_form_submit_build_entity('transaction', $form_state['#transaction'], $form, $form_state);
  $transaction = &$form_state['#transaction'];
  //print_r($form_state);die();
  field_attach_form_validate('transaction', $transaction, $form, $form_state);
  try {
    mcapi_transaction_validate($transaction, $form);
  }
  catch (Exception $e) {
   drupal_set_message($e->getMessage(), 'warning');
  }
}
/*
 * Universal transaction validation
 * called from transaction_form_validate
 */
function mcapi_transaction_validate($transaction, &$form = NULL){
  //no usecase is currently envisaged where someone might pay themselves
  if ($transaction->payer > 0) {
    if ($transaction->payer == $transaction->payee) {
      form_set_error('', t('A transaction must involve two different users'));
    }
  }
  //a transaction can only be complete if all these fields are filled in.
  if ($transaction->state == TRANSACTION_STATE_FINISHED) {
    $required = array('payer', 'payee',  'type');
    foreach ($required as $fieldname) {
      if (!$transaction->$fieldname) {
        $title = empty($form[$fieldname]['#title']) ? $fieldname : $form[$fieldname]['#title'];
        form_set_error($fieldname, t("@fieldname required for completed transaction", array('@fieldname' => $title)));
      }
    }
  }
  if (count(form_get_errors())) return;

  if ($transaction->payer && $transaction->payee) {
    //this concerns rules and database integrity, so we hand it off to the db controller
    //we're checking the balances, and trial-entering the transaction in the db
    $transaction = transaction_validate($transaction);
  }
}

/*
 * validate callback for transaction form participants - accepts uid only
 */
function mcapi_validate_transactor(&$element, $form_state) {
  $account = user_load($element['#value']);
  if (!$account->uid) {
    form_error($element, t('User ID does not exist: @user', array('@user' => $element['#value'])));
  }
  elseif(!user_access('transact', $account)) {
    form_error($element, t("User ID is not permitted to 'transact': @user", array('@user' => $account->name)));
  }
}

/*
 * Confirmation form before deleting a transaction
 * Couldn't be bothered to provide a theme callack for such a rarely used form.
 */
function mcapi_transaction_erase_confirm_form($form, $form_state, $transaction){
  $form['xid'] = array(
    '#type' => 'hidden',
    '#value' => $transaction->xid
  );
  $question = t('Are you sure you want to erase transaction #@xid?', array('@xid' => $transaction->xid));
  $path = 'transaction/'.$transaction->xid;
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
  $transaction = transaction_load($form_state['values']['xid']);
  transaction_erase($transaction);
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
 */
function mcapi_validate_state(&$element, $form_state) {
  if (!in_array($element['#value'], mcapi_transaction_states())) return;
  form_error($element, t('Invalid transaction state.') .' '. t('Suspected hacking attempt!'));
}

/*
 * Validate callback for transaction form 'type' element
 */
function mcapi_validate_ttype(&$element, $form_state) {
  // _element_validate() checks select widgets in the same way
  if (in_array( $element['#value'], module_invoke_all('mcapi_info', 'types'))) return;
  form_error($element, t('Invalid transaction type.') .' '. t('Suspected hacking attempt!'));
}


function mcapi_access_unrestricted() {
  return TRUE;
}
function mcapi_access_none() {
  return FALSE;
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
/*
function mcapi_access_currency($transaction, $account, $currency) {
  return currency_access($currency, $account);
}
*/

function mcapi_membership_transact($currency, $account) {
  return user_access('transact', $account);
}