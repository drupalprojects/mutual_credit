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
    'uri callback' => 'transaction_uri',
    'label callback' => 'transaction_label',
    'access callback' => 'transaction_access',
    'module' => 'mcapi',
    'fieldable' => TRUE,
    'entity keys' => array(
      'id' => 'xid',
      'name' => 'serial',//name is required by entity API module
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
    'load hook' => 'mcapi_xaction_load',//this isn't used but must avoid conflict with commerce module
    'view callback' => 'transaction_view', //used by entity module
    'view modes' => array(
      'certificate' => array(
        'label' => t('Certificate'),
        'custom settings' => FALSE,
      ),
      'sentence' => array(
        'label' => t('Main transaction as a sentence'),
        'custom settings' => FALSE,
      ),
      'sentences' => array(
        'label' => t('One sentence per transaction'),
        'custom settings' => FALSE,
      ),
    ),
    //entity API properties
    'creation callback' => 'new_transaction',
    'save callback' => 'transaction_insert_new',
    'export' => array(
      'default hook' => 'blah'
    )
  );

  // Search integration is provided by node.module, so search-related
  // view modes for nodes are defined here and not in search.module.
  if (module_exists('search')) {
    $entities['transaction']['view modes'] += array(
      'search_index' => array(
        'label' => t('Search index'),
        'custom settings' => FALSE,
      ),
      'search_result' => array(
        'label' => t('Search result'),
        'custom settings' => FALSE,
      ),
    );
  }
  return $entities;
}

function mcapi_menu_inc() {
  $items['admin/accounting'] = array(
    'title' => 'Accounting',
    'description' => "Manage all aspects of your community's money system",
    'page callback' => 'mcapi_accounting',
    'access arguments' => array('manage all transactions'),
    'file' => 'mcapi.inc',
    'weight' => 2
  );
  $items['transact'] = array(
    'title' => 'Record transaction',
    'description' => 'Log a transaction in the database',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('transaction_default_form'),
    'access arguments' => array('transact'),
    'file' => 'mcapi.inc',
  );
  $items['transact/default'] = array(
    'title' => 'Log',
    'description' => 'Full control over transaction creation',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );
  $items['admin/accounting/misc'] = array(
    'title' => 'Misc settings',
    'description' => "This package comes with it's own entity controller, which reads and writes to the database using an internal API. It is possible to write different entity controllers to work with different accouting systems. The provided entity controller is semi-financialised. It is very different from the drupal default, designed for content management, but it does only single entry accounting with no database-level error checking built in.",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_misc_options_form'),
    'access arguments' => array('manage all transactions'),
    'file' => 'mcapi.inc',
    'weight' => 10
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
    'title' => 'Transaction certificate',
    'title callback' => 'transaction_menu_title',
    'title arguments' => array(1),
    // The page callback also invokes drupal_set_title() in case
    // the menu router's title is overridden by a menu link.
    'page callback' => 'transaction_view',
    'page arguments' => array(1, 'certificate'),
    'access callback' => 'transaction_view_access',
    'access arguments' => array(1),
    'file' => 'mcapi.inc',
    'type' => MENU_CALLBACK,
  );
  $items['transaction/%transaction/view'] = array(
    'title' => 'View',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );
  $items['transaction/%transaction/undo'] = array(
    'title' => 'Undo',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_transaction_undo_confirm_form', 1),
    'access callback' => 'transaction_undo_access',
    'access arguments' => array(1),
    'weight' => 1,
    'type' => MENU_LOCAL_ACTION,
    'file' => 'mcapi.inc',
  );
  return $items;
}
/*
 * implements hook_menu_alter
 * (this file is included in mcapi_menu)
 */
function mcapi_menu_alter(&$items) {
  foreach (array_keys($items) as $key) {
    //we don't need to see the page for arranging the bundle fields,
    if (substr($key, 0, 41) == 'admin/accounting/edit/display') {
      unset($items[$key]);
    }
  }
  unset($items['admin/accounting/fields/worth/delete']);
  //but we do want to manage the fields for two entity types on one page.
  $items['admin/accounting/edit/fields']['title'] = 'Transaction fields';
}

/*
 * menu_callback top level config page for mcapi
 */
function mcapi_accounting() {
  module_load_include('admin.inc', 'system');
  return system_admin_menu_block_page();
}

/*
 * helper to get block settings and load whichever user we like
 * may return nothing if no user is determined
 */
function _mcapi_block_settings($delta) {
  $currencies = &drupal_static('currencies');
  $settings = variable_get('block_'. $delta, array('currcodes' => array_keys($currencies), 'user_source' => 0));
  if (@$settings['user_source'] == 0) {
    if (arg(0) == 'user') {
      $settings['account'] = user_uid_optional_load(arg(1));
    }
    else return;
  }
  else {
    $settings['account'] = $GLOBALS['user'];
  }
  return $settings;
}

function transaction_menu_title($transaction = NULL) {
  return t('Transaction certificate #@serial', array('@serial' => $transaction->serial));
}

function transaction_default_form($form, &$form_state) {
  $form = transaction_form($form, $form_state);
  $form['state']['#type'] = 'value';
  $form['type']['#type'] = 'value';
  $form['creator']['#type'] = 'value';
  return $form;

}

/**
 * All transaction forms should use this function as a basis using hook_forms
 */
function _transaction_form(&$form, &$form_state, $transaction) {
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
  $form['type'] = array(
    '#title' => t('Transaction type'),
    '#options' => drupal_map_assoc(module_invoke_all('mcapi_info_types')),
    '#type' => 'select',
    '#default_value' => $transaction->type ? $transaction->type : 'default',
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
  field_attach_form('transaction', $transaction, $form, $form_state);
  unset($form['worth'][LANGUAGE_NONE]['add_more']);

  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Record'),
    //this prevents double click, but doesn't prevent going back and resubmitting the form
    '#attributes' => array('onclick' => "this.disabled=true,this.form.submit();")
  );
  $form['actions']['#weight'] = 25;
  $form['#validate'] = array('transaction_form_validate');
  $form['#submit'] = array('transaction_form_submit');
}

/*
 * standard form-level validation callback for ALL transaction forms
 * fields have already been validated individually
 * By this time each of the form values should be well enough validated not to fail on writing to db
 */
function transaction_form_validate($form, &$form_state) {
  $transaction = (object)$form_state['values'];
  //populate the core transaction values from $form_state
  entity_form_submit_build_entity('transaction', $transaction, $form, $form_state);
  //validate the Field API fields
  field_attach_form_validate('transaction', $transaction, $form, $form_state);
  if (form_get_errors()) return;
  try{
    //keep a copy of the built transaction, and its dependencies for validation elsewhere
    $form_state['transactions'] = transaction_insert_new($transaction, FALSE);
  }
  catch (Exception $e) {
    form_set_error('', $e->getMessage());
  }
}


/*
 * form submit callback from the base transaction form, hence used by all forms
 * hence must be in .module file
 * this is what actually saves the data
 */
function transaction_form_submit($form, &$form_state) {
  $form_build_id = $form_state['values']['form_build_id'];
  //check the form hasn't been submitted already
  $submitted = db_query('SELECT count(form_build_id) FROM {mcapi_submitted} where form_build_id = :id', array(':id' => $form_build_id))
  ->fetchField();
  if ($submitted) {
    drupal_set_message(t('Transaction was already submitted'), 'error');
    return;
  }
  //check that the form hasn't been submitted already
  db_query('INSERT INTO {mcapi_submitted} (form_build_id, time) VALUES (:form_build_id, :time)',
    array(':form_build_id' => $form_build_id, ':time' => REQUEST_TIME)
  );
  form_state_values_clean($form_state);
  $transaction = (object)$form_state['values'];
  //build the transaction object from the $form_state[values]
  entity_form_submit_build_entity('transaction', $transaction, $form, $form_state);
  try{
    $transactions = transaction_insert_new($transaction, TRUE);
  }
  catch (Exception $e) {
    form_set_error('', $e->getMessage());
  }
  $form_state['values']['serial'] = $transaction->serial;
}


/*
 *
 */
function mcapi_misc_options_form($form_state) {
  cache_clear_all('*', 'cache');
  $controllers = module_implements('transaction_controller');
  $form['mcapi_controller'] = array(
    '#title' => 'Entity controller module',
    '#description' => t('Choose which module, with a .controller file, to use.'),
    '#type' => 'radios',
    '#options' => drupal_map_assoc($controllers),
    '#default_value' => variable_get('mcapi_controller', 'mcapi'),
    '#ajax' => array(
      'callback' => 'mcapi_controller_options_form_ajax',
      'wrapper' => 'mcapi-controller-options-form',
    ),
  );

  global $databases;
  if (count($databases) > 1) {
    foreach($databases as $key => $data) {
      $options[$key] = $key;
    }
    $form['master_slave'] = array(
      '#title' => t('Databases'),
      '#description' => t('See @file for how to enable more databases.', array('@file' => 'settings.php')) .' '.
        t('Write accounting information to more than one database, and read from one database, making a sort of master/slave arrangement.') .' '.
        t("Don't forget each database connection itself can have master/slave arrangement, but we are concerned only with accounting here.") .' '.
        t("the database should be appropriate for the controller."),
      '#type' => 'fieldset',
      '#attributes' => array('id' => 'master-slave'),
    );
    $form['master_slave']['mcapi_db_write'] = array(
      '#title' => t('Write accounting information to'),
      '#description' => t('See @file for how to enable more databases.', array('@file' => 'settings.php')) .' '.
        t('Use with caution as fields added to transactions and currencies will still be saved locally') .' '.
        t("the database should be appropriate for the controller."),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => variable_get('mcapi_db_write', array('default' => 'default')),
      '#ajax' => array(
        'callback' => 'mcapi_controller_options_form_ajax',
        'wrapper' => 'master-slave',
      ),
    );
    $form['master_slave']['mcapi_db_read'] = array(
      '#title' => t('Read accounting information from'),
      '#type' => 'select',
      '#options' =>  $options,
      '#default_value' => variable_get('mcapi_db_read', 'default'),
      '#weight' => 1,
    );
  }
  $form['mcapi_controller_delete_mode'] = array(
    '#title' => t('Undo mode'),
    '#description' => t('What should happen when a user or admin deletes a transaction.?') .' '.
      t("Some system operations may 'scratch' transactions") .' '.
      t('Cannot be changed after a transaction has been undone'),
    '#type' => 'radios',
    '#options' => array(
      MCPI_CURRENCY_UNDO_DELETE => t('Wipe slate - remove transactions from database'),
      MCPI_CURRENCY_UNDO_ERASE => t('Scratch - use deleted transaction state'),
      MCPI_CURRENCY_UNDO_REVERSE => t('Reverse - create an equal and opposite transaction'),
    ),
    '#default_value' => variable_get('mcapi_controller_delete_mode', MCPI_CURRENCY_UNDO_REVERSE),
    '#disabled' => !variable_get('change_undo_mode', FALSE)
  );
  return system_settings_form($form);
}

/*
 * implements hook_accounting_validate
 * Some wiggle room has been left in case for any reason we want to store transactions with missing fields
 */
function mcapi_accounting_validate($transactions){
  $errors = array();
  foreach ($transactions as $transaction) {
    //no usecase is currently envisaged where someone might pay themselves in one currency in one transaction
    if ($transaction->payer > 0) {
      if ($transaction->payer == $transaction->payee) {
        $errors[] =t('A transaction must involve two different users');
      }
    }
    //a transaction can only be finished if all these fields are filled in.
    if ($transaction->state >= TRANSACTION_STATE_FINISHED) {
      $required = array('payer', 'payee',  'type');
      foreach ($required as $fieldname) {
        if (!$transaction->$fieldname) {
          $errors[] = t("@fieldname required for completed transaction", array('@fieldname' => $fieldname));
        }
      }
    }
    //check that each user has permission to use each currency
    foreach ($transaction->worth[LANGUAGE_NONE] as $key => $flow) {
      $currency = currency_load($flow['currcode']);
      $access_callback = $currency->currency_access['membership'];
      foreach (array('payer', 'payee') as $uid) {
        if ($access_callback($transaction->$uid) == FALSE) {
          $errors[] = t('User @name cannot use @currency', array('@name' => user_load($transaction->$uid)->name, '@currency' => $currency->human_name));
        }
      }
    }
  }
  if ($errors) {
    throw new Exception(implode("<br />", $errors));
  }
}

/*
 * validate callback for transaction form participants - accepts uid only
 * the $element value may be a string, depending on the widget used
 * in which case the form_state value will have been changed by mcapi_validate_usernames()
 */
function mcapi_validate_transactor(&$element, $form_state) {
  $uid = &$form_state['values'][$element['#name']];
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
function mcapi_transaction_undo_confirm_form($form, $form_state, $transaction){
  $form['serial'] = array(
    '#type' => 'hidden',
    '#value' => $transaction->serial
  );
  $question = t('Are you sure you want to undo transaction #@serial?', array('@serial' => $transaction->serial));
  $path = 'transaction/'.$transaction->serial;
  $description = '';
  if (isset($transaction->children) && count($transaction->children)) {
    $description = t('This includes dependent transactions');
  }
  $yes = t('Delete');
  $no = t('Cancel');
  $name = 'undo_transaction';
  return confirm_form($form, $question, $path, $description, $yes, $no, $name);
}
/*
 * Submit handler for deleting a transaction
 */
function mcapi_transaction_undo_confirm_form_submit($form, $form_state) {
  transactions_undo(array($form_state['values']['serial']));
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
 * as a form element validation callback, this is assumed to happen BEFORE transactions_validate is called
 */
function mcapi_validate_state(&$element, &$form_state) {
  $states = mcapi_get_states('#full');
  if (!isset($states[$element['#value']])) {
    form_error($element, t('Invalid transaction state: @value.', array('@value' => $element['$value'])));
  }
}

/*
 * Validate callback for transaction form 'type' element
 */
function mcapi_validate_ttype(&$element, $form_state) {
  $values = is_array($element['#value']) ? $element['#value'] : array($element['#value']);
  foreach ($values as $value) {
    if (!in_array($value, module_invoke_all('mcapi_info_types'))) {
      form_error($element, t('Invalid transaction type: @value', array('@value' => $value)));
    }
  }
}

/*
 * transaction_access callbacks
 */
function transaction_access_anyone($transaction, $account = NULL) {
  return TRUE;
}
function transaction_access_authenticated($transaction, $account = NULL) {
  if (!$account) $account = $GLOBALS['user'];
  return $account->uid;
}
function transaction_access_accountant($transaction, $account = NULL) {
  return user_access('manage all transactions', $account);
}
function transaction_access_participants($transaction) {
  return in_array($GLOBALS['user']->uid, array($transaction->payer, $transaction->payee));
}
function transaction_access_payee($transaction) {
  return $GLOBALS['user']->uid == $transaction->payee;
}
function transaction_access_payer($transaction) {
  return $GLOBALS['user']->uid == $transaction->payer;
}


/*
 * currency access callbacks
 * determine a segment of users
 * each of these returns TRUE or FALSE if passed a User ID
 * or a list of User IDs if passed nothing
 */
function user_segments_transact($uid = 0) {
  if ($uid && $account = user_load($uid)) {
    return user_access('transact', $account);
  }
  return _matslats_role_get_users('transact', $uid);
}
function user_segments_manage_all_transactions($uid = 0) {
  if ($uid && $account = user_load($uid)) {
    return user_access('manage all transactions', $account);
  }
  return _matslats_role_get_users('manage all transactions');
}
function user_segments_all($uid = 0) {
  $query = db_select('users', 'u')->fields('u', array('uid'))->condition('status', 1);
  if ($uid) $query->condition('uid', $uid);
  $uids = $query->execute()->fetchcol();
  if ($uid) return in_array($uid, $uids);
  return $uids;
}
//utility function for the above
function _matslats_role_get_users($permission) {
  $rids = user_roles(TRUE, $permission);
  if (empty($rids)) return array(1);
  $query = db_select('users_roles', 'ur');
  $query->join('users', 'u', 'u.uid = ur.uid');
  return $query->fields('ur', array('uid'))
    ->condition('ur.rid', array_keys($rids))
    ->condition('u.status', TRUE)
    ->execute()
    ->fetchCol();
}

/*
 * access control wrapper for currency
 * if $account is specified, returns TRUE or FALSE
 * otherwise returns a list of users
 */
function currency_access($op, $currcode, $uid) {
  $function = currency_load($currcode)->currency_access[$op];
  return $function($uid);
}

/*
 * returns a form element for picking a currency
 * if $picker_options is an array of currencies keyed by currcode, it returns a select
 * if $picker_options is NULL it returns a hidden field
 */
function currency_picker_element(array $currcodes, $default = NULL, $multiple = FALSE) {
  if (count($currcodes) < 2) {
    return array(
      '#type' => 'hidden',
      '#default_value' => current($currcodes)
    );
  }
  return array(
    '#title' => t('Currency'),
    '#type' => 'select',
    '#options' =>  _currency_picker_element_options($currcodes),
    '#default_value' => $default,
    '#multiple' => $multiple,
  );
}
//build a key value array of currency codes and currency names
//$currencies is an array of the form returned by currencies_load
//if $currencies is empty, it will assume you mean all currencies
function _currency_picker_element_options(array $currcodes) {
  $currencies = &drupal_static('currencies');
  foreach ($currcodes as $currcode) {
    $options[$currcode] = $currencies[$currcode]->human_name;
  }
  return $options;
}


/*
 * action callback
 * mail the transaction participants, and cc
 */
function mcapi_transaction_mail_action($old_cluster, $params, $new_state) {
  if ($new_state != TRANSACTION_STATE_FINISHED) return;
  $uids = array($old_cluster->payer,$old_cluster->payee);
  $params['transaction'] = $old_cluster;
  _mcapi_transaction_mail_action($uids, $params);
}

function _mcapi_transaction_mail_action($uids, $params) {
  //check the filter-by-transaction type
  if (empty ($params['transaction_types'][$params['transaction']->type])) return;
  foreach ($uids as $uid) {
    if ($GLOBALS['user']->uid != $uid) {
      $params['user'] = user_load($uid);
      drupal_mail('mcapi', 'transaction_mail_action', $params['user']->mail, user_preferred_language($params['user']), $params);
    }
  }
  //this is done separately because we start not with a uid but with a mail which may or not belong to an account
  if ($params['cc']) {
    global $language;
    $account = user_load_by_mail($params['cc']);
    $language = $account->uid > 0 ? user_preferred_language($params['user']) : $language;
    drupal_mail('mcapi', 'transaction_mail_action', $params['cc'], user_preferred_language($params['user']), $params);
  }
}

/*
 * settings form callback for action mcapi_transaction_mail_action
 */
function mcapi_transaction_mail_action_form($context = array()) {
  if (empty($context['body'])) {
    $context['body'] = "[user:name],\n\n".
      t('A transaction for [transaction:worth] has been finalised')."\n\n".
      t('More details can be found at:') ."\n[transaction:uri]\n\n".
      t('To change your notification settings, visit your profile at:') ."\n[user:edit-url]\n\n".
      t('The team at [site:name]');
  }
  if (empty($context['subject'])) {
    $context['subject'] = t('Transaction saved by [transaction:creator]');
  }
  $form = _mcapi_mail_template_fields($context);
  $def_types = isset($context['transaction_types']) ? $context['transaction_types'] : array();
  $form['transaction_types'] = _transaction_type_selector($def_types, TRUE, array('mass', 'child'));
  $form['transaction_types']['#description'] = t('Only send for these transaction types.');
  unset($form['transaction_types']['#options']['auto'], $form['transaction_types']['#options']['undo']);
  return $form;
}

function _transaction_type_selector($default, $multiple = TRUE, $exclude = array()) {
  $types = array_diff(mcapi_get_types(), $exclude);
  return array(
    '#title' => $multiple ? t('Transaction types') : t('Transaction type'),
    '#type' => $multiple ? 'checkboxes' : 'select',
    '#options' => drupal_map_assoc($types),
    '#default_value' => $default,
    '#required' => TRUE,
    '#element_validate' => array('mcapi_validate_ttype')
  );
}

function _mcapi_mail_template_fields($defaults = array(), $tokens = array(), $required = TRUE) {
  return array(
    'mail' => array(
      '#title' => t('Mail template'),
      '#description' => $tokens,
      '#type' => 'fieldset',
      'subject' => array(
        '#title' => t('Subject'),
        '#type' => 'textfield',
        '#default_value' => empty($defaults['subject']) ? '' : $defaults['subject'],
        '#required' => $required,
      ),
      'body' => array(
        '#title' => t('Body'),
        '#type' => 'textarea',
        '#default_value' => empty($defaults['body']) ? '' : $defaults['body'],
        '#required' => $required,
        '#weight' => 1
      ),
      'cc' => array(
        '#title' => t('Carbon copy'),
        '#description' => t('Send an extra copy to this address'),
        '#type' => 'textfield',
        '#default_value' => empty($defaults['cc']) ? '' : $defaults['cc'],
        '#element_validate' => array('_mcapi_validate_mail_element'),
        '#weight' => 2
      ),
      'token_tree' => array(
        '#theme' => 'token_tree',
        '#token_types' => $tokens,//array('user'),
        '#global_types' => FALSE,
      )
    )
  );
}

function mcapi_transaction_mail_action_submit($form, $form_state) {
  return $form_state['values'];
}

/*
 * implements hook_mail
 */
function mcapi_mail($key, &$message, $params) {
  if ($key == 'transaction_mail_action') {
    // We do not sanitize the token replacement, since the output of this
    // replacement is intended for an e-mail message, not a web browser.
    $options = array('language' => $message['language'], 'sanitize' => FALSE);
    $message['subject'] =  token_replace($params['subject'], $params, $options);
    $message['body'][] =  token_replace($params['body'], $params, $options);
  }
}

/*
 * action callback for hook_transactions_alter
 * Alters the original transaction and/or returns a child
 */
function mcapi_supplementary_transaction_action($transactions, $settings) {
  $transaction = current($transactions);
  $currcode = isset($settings['currcode']) ? $settings['currcode'] : 'def_drup';
  $quant = _mcapi_calc($settings['rate'], $transaction->worth[LANGUAGE_NONE][0]['quantity']);
  $child = array(
    'type' => 'child',
    'serial' => $transaction->serial,//note how this is inherited
    'payer' => $transaction->{$settings['source']},
    'payee' => $settings['recipient'],
    'state' => $transaction->state,
    'worth' => array(
      LANGUAGE_NONE => array(
        0 => array(
          'quantity' => mcapi_round($quant, $currcode, $settings['round'] == 'up'),
          'currcode' => $currcode
        )
      )
    )
  );
  return new_transaction($child);
}

/*
 * Settings form callback for mcapi_pay_someone_action
 * odd that form_state isn't passed
 * this means we can't do form_load_include
 * $context = array(
 *  [actions_label] => Record transaction
 *  [actions_type] => transaction
 * )
 */
function mcapi_supplementary_transaction_action_form($context) {
  $form['#description'] = t('Assumes the first currency in the transaction');
  $def_currcode = isset($context['currcode']) ? $context['currcode'] : 'def_drup';
  $currencies = &drupal_static('currencies');
  $form['currcode'] = currency_picker_element(array_keys($currencies), $def_currcode, FALSE);
  $form['source'] = array(
    '#title' => t('Source'),
    '#type' => 'radios',
    '#options' => array('payer' => t('Payer'), 'payee' => t('Payee')),
    '#default_value' => isset($context['source']) ? $context['source'] : 'payer',
  );
  $form['recipient'] = array(
    '#type' => 'textfield',
    '#title' => t('Account number to be credited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => isset($context['recipient']) ? $context['recipient'] : 1,
    '#element_validate' => array('mcapi_validate_usernames', 'mcapi_validate_transactor'),
    '#autocomplete_path' => 'user/autocomplete',
    '#weight' => 1,
  );
  form_load_include($form_state, 'field.inc', 'mcapi');
  _add_rate_fields($form, 3);
  return $form;
}
function mcapi_supplementary_transaction_action_submit($form, $form_state) {
  return $form_state['values'];
}

function mcapi_description_field($form, $form_state) {
//  form_load_include($form_state, 'inc', 'mcapi');
  $options = array('' => 'none');
  $options += _mcapi_get_text_instances();
  $form['transaction_description_field'] = array(
    '#type' => 'select',
    '#title' => 'Description field',
    '#description' => t("Which of the text fields below is used for the transaction description?."),
    '#options' => $options,
    '#default_value' => variable_get('transaction_description_field', ''),
  );
  return system_settings_form($form);
}
function _mcapi_get_text_instances() {
  $options = array();
  if (substr($_SERVER['SCRIPT_NAME'], -9) == 'index.php' && module_exists('field')) {//coz field_info_fields isn't available during update.php
    foreach(field_info_fields() as $field) {
      if ($field['module'] == 'text' && isset($field['bundles']['transaction'])) {
        $options[$field['field_name']] = $field['field_name'];
      }
    }
  }
  return $options;
}