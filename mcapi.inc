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
        'label' => t("A transaction certificate"),
        'custom settings' => FALSE,
      ),
    ),
    //entity API properties
    'creation callback' => 'entity_metadata_create_transaction',
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
  $items['admin/accounting/config'] = array(
    'title' => 'Configuration',
    'type' => MENU_DEFAULT_LOCAL_TASK
  );
  $items['transact'] = array(
    'title' => 'Record transaction',
    'description' => 'Log a transaction in the database',#co
    'page callback' => 'drupal_get_form',
    'page arguments' => array('transaction_default_form'),
    //we use this permission but it's possible to create a paradox since each currency has its own permission
    //if we knew what currency we would use callback 'currency_access' and arg 'membership'
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
    'title callback' => 'transaction_label',
    'title arguments' => array(1),
    // The page callback also invokes drupal_set_title() in case
    // the menu router's title is overridden by a menu link.
    'page callback' => 'transaction_view',
    'page arguments' => array(1, 'certificate'),
    'access callback' => 'transaction_operation_access',
    'access arguments' => array('view', 1),
    'type' => MENU_CALLBACK,
  );
  $items['transaction/%transaction/view'] = array(
    'title' => 'View',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );

  foreach (transaction_operations(FALSE) as $op => $info) {
    $items["transaction/%transaction/$op"] = array(
      'title' => $info['imperative'],
      'description' => $info['description'],
      'page callback' => 'drupal_get_form',
      'page arguments' => array('transaction_operation_form', $op, 1),
      'access callback' => 'transaction_operation_access',
      'access arguments' => array($op, 1),
      'weight' => 1,
      'type' => MENU_LOCAL_ACTION,
    );
    if (isset($info['file_extension'])) {
      $items["transaction/%transaction/$op"]['file'] = $info['module'] .'.'. $info['file_extension'];
    }
  }
  return $items;
}

function transaction_operation_form($form, &$form_state, $op, $transaction) {
  $operations = transaction_operations();
  $info = &$operations[$op];
  if (isset($info['file_extension'])) {
    form_load_include($form_state, $info['file_extension'], $info['module']);
  }
  mcapi_hide_tabs();
  $form['serial'] = array(
    '#type' => 'hidden',
    '#value' => $transaction->serial
  );
  $form['operation'] = array(
    '#type' => 'hidden',
    '#value' => $op
  );
  $form['#redirect'] = isset($info['redirect']) ? $info['redirect'] : 'transaction/'. $transaction->serial;
  $form_callback = 'mcapi_operation_'.$op.'_form';
  if (function_exists($form_callback)) {
    $form += $form_callback($transaction);
  }
  $form['certificate'] = transaction_view(array($transaction));
  $form['certificate']['#weight'] = 1;
  $form = confirm_form(
    $form,
    $info['sure_message'],
    isset($info['redirect']) ? $info['redirect'] : 'transaction/'. $transaction->serial,
    '',
    $info['imperative'],
    t('Cancel'),
    $op
  );
  return $form;
}

function transaction_operation_form_submit($form, $form_state) {
  //I think the access has been checked already...
  $real_submit = 'transaction_operation_do_'.$form_state['values']['operation'];
  $real_submit($form_state['values']['serial'], $form_state['values']);
  //pull the trigger
  drupal_goto($form['#redirect']);
}


/*
 * implements hook_menu_alter
 * (this file is included in mcapi_menu)
 */
function mcapi_menu_alter(&$items) {
  $items['admin/accounting/fields']['title'] = 'Transactions FieldAPI';
}

/*
 * menu_callback top level config page for mcapi
 */
function mcapi_accounting() {
  module_load_include('admin.inc', 'system');
  return system_admin_menu_block_page();
}

/*
 * menu callback
 * build the most basic possible transaction form
 */
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
function _transaction_form(&$form, &$form_state) {
  //the default payer and payee widgets will autocomplete any user on the system, and permissions will be checked later
  $form['payer'] = array(
    '#title' => t('Account to be debited'),
    '#type' => 'user_chooser_permissions',
    '#args' => array('transact'),
    '#allow_me' => TRUE,
    '#default_value' => $form['#transaction']->payer,
    '#weight' => 6,
  );
  $form['payee'] = array(
    '#title' => t('Account to be credited'),
    '#type' => 'user_chooser_permissions',
    '#args' => array('transact'),
    '#allow_me' => TRUE,
    '#default_value' => $form['#transaction']->payee,
    '#weight' => 9,
  );
  $form['type'] = array(
    '#title' => t('Transaction type'),
    '#options' => drupal_map_assoc(module_invoke_all('mcapi_info_types')),
    '#type' => 'select',
    '#default_value' => $form['#transaction']->type,
    '#element_validate' => array('mcapi_validate_ttype'),
    '#required' => TRUE,
    '#weight' => 15
  );
  $form['state'] = array(
    '#title' => t('State'),
    '#description' => mcapi_get_states('#description'),
    '#type' => 'radios',
    '#options' => mcapi_get_states('#options'),
    '#default_value' => $form['#transaction']->state,
    '#element_validate' => array('mcapi_validate_state'),
    '#weight' => 18
  );
  $form['creator'] = array(
    '#title' => t('Recorded by'),
    '#type' => 'user_chooser_permissions',
    '#default_value' => $form['#transaction']->creator,
    '#args' => array('transact'),
    '#allow_me' => TRUE,
    '#required' => TRUE,
    '#weight' => 20,
  );

  $form['buttons'] = array(
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Record'),
      //this prevents double click, but doesn't prevent going back and resubmitting the form
      '#attributes' => array('onclick' => "this.disabled=true,this.form.submit();"),
    ),
    '#weight' => 25
  );
}

/*
 * standard form-level validation callback for ALL transaction forms
 * fields have already been validated individually
 * By this time each of the form values should be well enough validated not to fail on writing to db
 */
function transaction_form_validate($form, &$form_state) {
  $transaction = entity_metadata_create_transaction($form_state['values']);
  //populate the core transaction values from $form_state
  entity_form_submit_build_entity('transaction', $transaction, $form, $form_state);
  //validate the Field API fields
  field_attach_form_validate('transaction', $transaction, $form, $form_state);
  if (form_get_errors()) return;
  try{
    if ($form['#skip_alter']) {
      $transactions = array($transaction);
      $form_state['transactions'] = transactions_insert($transactions, FALSE);
    }
    else {
      //keep a copy of the built transaction, and its dependencies for validation elsewhere
      $form_state['transactions'] = transaction_insert_new($transaction, FALSE);
    }
  }
  catch (Exception $e) {
    form_set_error('', $e->getMessage());
  }
}


/*
 * form submit callback from the base transaction form, hence USED BY ALL forms
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
  $transaction = entity_metadata_create_transaction($form_state['values']);
  //build the transaction object from the $form_state[values]
  entity_form_submit_build_entity('transaction', $transaction, $form, $form_state);

  try{
    if ($form['#skip_alter']) {
      $transactions = array($transaction);
      transactions_insert($transactions, TRUE);
    }
    else {
      transaction_insert_new($transaction, TRUE);
    }
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
      MCAPI_CURRENCY_UNDO_DELETE => t('Wipe slate - remove transactions from database'),
      MCAPI_CURRENCY_UNDO_ERASE => t('Scratch - use deleted transaction state'),
      MCAPI_CURRENCY_UNDO_REVERSE => t('Reverse - create an equal and opposite transaction'),
    ),
    '#default_value' => variable_get('mcapi_controller_delete_mode', MCAPI_CURRENCY_UNDO_REVERSE),
    '#disabled' => !variable_get('change_undo_mode', FALSE)
  );

  $form['mcapi_sentence'] = array(
    '#title' => t('Transaction sentence template'),
    '#description' => t('Use the tokens to define how the transaction will read when displayed in sentence mode'),
    '#type' => 'textfield',
    '#default_value' => variable_get('mcapi_sentence'),
    '#weight' => 5
  );
  $form['token_tree'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('transaction'),
    '#global_types' => FALSE,
    '#weight' => 6
  );

  return system_settings_form($form);
}

function mcapi_mixed_transactions($form) {
  $form['field']['mcapi_mixed_transactions'] = array(
    '#title' => t('Currencies per transaction'),
    '#type' => 'radios',
    '#options' => array(
      0 => t('One currency per transaction'),
      1 => t('Many currencies per transaction')
    ),
    '#default_value' => variable_get('mcapi_mixed_transactions', TRUE)
  );
  return system_settings_form($form);
}

/*
 * implements hook_accounting_validate
 * Some wiggle room has been left in case for any reason we want to store transactions with missing fields
 */
function mcapi_accounting_validate(&$transactions){
  $errors = array();
  foreach ($transactions as $key => $transaction) {
    //no usecase is currently envisaged where someone might pay themselves in one currency in one transaction
    if ($transaction->payer > 0) {
      if ($transaction->payer == $transaction->payee) {
        if ($key == 0) {
          $errors[] = t('A transaction must involve two different users');
        }
        else {
          unset($transactions[$key]);
        }
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
  }
  if ($errors) {
    throw new Exception(implode("<br />", $errors));
  }
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
 * do callback derived from mcapi_transaction_operations
 */
function transaction_operation_do_undo($serial) {
  transactions_undo(array($serial));
}

/*
 * access callback derived from mcapi_transaction_operations
 */
function transaction_operation_access_undo($callbacks, $transaction) {
  if ($transaction->state != TRANSACTION_STATE_FINISHED) return FALSE;
  return _transaction_check_access_callbacks($callbacks, $transaction);
}
/*
 * helper function to run through all the access callbacks after the operation's own access callback has done its tests
 */
function _transaction_check_access_callbacks($callbacks, $transaction) {
  foreach ($callbacks as $function) {
    if ($function($transaction)) return TRUE;
  }
  return FALSE;
}

/*
 * transaction_access callbacks
 * $ops are determined in hook_transation_operations
 */
function transaction_access_callback_anyone($transaction) {
  return TRUE;
}
function transaction_access_callback_perm_transact($transaction) {
  return user_access('transact');
}
function transaction_access_callback_perm_manage_all($transaction) {
  return user_access('manage all transactions');
}
function transaction_access_callback_payee($transaction) {
  return $account->uid == $transaction->payee;
}
function transaction_access_callback_payer($transaction) {
  return $account->uid == $transaction->payer;
}


/*
 * action callback
 * mail the transaction participants, and cc
 * $transaction may have dependents
 */
function mcapi_transaction_mail_action($transaction, $settings, $new_state) {
  //pending transaction notifications are handled by the signatures module
  if ($new_state != TRANSACTION_STATE_FINISHED) return;
  //the actions settings allow to filter by transaction type
  if (empty($settings['transaction_types'][$transaction->type])) return;

  $params = $settings + array('transaction' => $transaction);
  foreach (array($transaction->payer, $transaction->payee) as $uid) {
    if ($GLOBALS['user']->uid != $uid) {
      $params['user'] = user_load($uid);
      drupal_mail('mcapi', 'transaction_mail_action', $params['user']->mail, user_preferred_language($params['user']), $params);
    }
  }
}

/*
 * settings form callback for action mcapi_transaction_mail_action
 */
function mcapi_transaction_mail_action_form($context = array()) {
  $context += array(//set the defaults
    'subject' => t('Transaction saved by [transaction:creator]'),
    'body' => "[user:name],\n\n".
      t('A transaction for [transaction:worth] has been finalised')."\n\n".
      t('More details can be found at:') ."\n[transaction:url:absolute]\n\n".
      t('To change your notification settings, visit your profile at:') ."\n[user:edit-url]\n\n".
      t('The team at [site:name]')
  );
  $form['mail'] = mcapi_mail_config_fieldset($context, array('transaction', 'user'), TRUE, 5);
  $form['mail']['#description'] = t('Mail will be sent to the payer and payee, except for the logged in user.');
  $def_types = isset($context['transaction_types']) ? $context['transaction_types'] : array();
  $form['transaction_types'] = _transaction_type_selector($def_types, TRUE, array('mass', 'child'));
  $form['transaction_types']['#description'] = t('Only send for these transaction types.');
  unset($form['transaction_types']['#options']['auto'], $form['transaction_types']['#options']['undo']);
  return $form;
}

function _transaction_type_selector($default, $multiple = TRUE, $exclude = NULL) {
  if (!is_array($exclude)) {
    $exclude = array('child');
  }
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

/*
 * retrieve a form fieldset which allows a mail to be configured.
 * $key is either a variable name prefix or an array containing the defaults subject and body and optionallly cc
 *
 */
function mcapi_mail_config_fieldset($key, $tokens = array('transaction'), $required = TRUE, $weight = 0) {
  if (is_array($key)) {
    extract($key);
    $key = '';
  }
  else{
    $key .= '_';
    $subject = variable_get($key . 'subject');
    $body = variable_get($key . 'body');
    $cc = variable_get($key . 'cc', NULL);
  }
  $fieldset =  array(
    '#title' => t('Mail template'),
    '#type' => 'fieldset',
    '#weight' => $weight,
    $key.'subject' => array(
      '#title' => t('Subject'),
      '#type' => 'textfield',
      '#default_value' => $subject,
      '#required' => $required,
    ),
    $key.'body' => array(
      '#title' => t('Body'),
      '#type' => 'textarea',
      '#default_value' => $body,
      '#required' => $required,
      '#weight' => 2
    ),
    'token_tree' => array(
      '#theme' => 'token_tree',
      '#token_types' => $tokens,//array('user'),
      '#global_types' => FALSE,
      '#weight' => 3
    )
  );
  if (isset($cc)) {
    $fieldset[$key.'cc'] = array(
      '#title' => t('Carbon copy'),
      '#description' => t('Send an extra copy to this address'),
      '#type' => 'textfield',
      '#default_value' => $cc,
      '#element_validate' => array('_mcapi_validate_mail_element'),
      '#weight' => 1
    );
  }
  return $fieldset;
}

function mcapi_transaction_mail_action_submit($form, $form_state) {
  form_state_values_clean($form_state);
  return $form_state['values'];
}


//this should really be in core...
function _mcapi_validate_mail_element($element, $form_state) {
  if ($element['#value'] && !valid_email_address($element['#value'])) {
    form_error($element, t('That e-mail address is not valid.'));
  }
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
 * action callback
 * mail the transaction participants, and cc
 * $transaction may have dependents
 */
function mcapi_transaction_entity_uid($dunno, $context) {
  extract($context);
  $entity = $$group;
  $transaction = array(
    //allow a price to be fixed on the entity using the worth field.
    'worth' => isset($entity->worth) ? $entity->worth : $worth_items['worths'],
    'type' => 'auto',
    'state' => TRANSACTION_STATE_FINISHED
  );
  $transaction['description'] = $description;

  if ($direction == 'uidpays') {
    $transaction['payee'] = 1;
    $transaction['payer'] = $entity->uid;
  }
  else {
    $transaction['payee'] = $entity->uid;
    $transaction['payer'] = 1;
  }
  $transactions = array(entity_metadata_create_transaction($transaction));
  try{
    transactions_insert($transactions, TRUE);
  }
  catch (Exception $e) {
    drupal_set_message(t('Automated transaction failed: @message', array('@message' => $e->message)), 'error');
  }
}

/*
 * Settings form callback for action mcapi_transaction_entity_uid
 */
function mcapi_transaction_entity_uid_form($context) {
  $context += array(
    'mapping' => 'uidpays',
    'transaction_types' => array(),
    'otheruser' => 1,
    'worth_items' => array('worths' => array(), 'round' => 'down')
  );
  $form['direction'] = array(
    '#title' => t('Direction'),
    '#type' => 'radios',
    '#options' => array(
      'uidpays' => t('Account 1 pays user'),
      'paysuid' => t('User pays account 1'),
    ),
    '#default_value' => isset($context['mapping']) ? $context['mapping'] : 'payerpays',
  );
  mcapi_configure_worth_field($form, $context['worth_items'], TRUE, 'worth_items');
  $form['worth_items']['#description'] = t("this value will be overwritten if the passed entity has the 'worth' field");
  return $form;
}
/*
 * configuration action submit callback
 */
function mcapi_transaction_entity_uid_submit($form, $form_state) {
  form_state_values_clean($form_state);
  return $form_state['values'];
}


/*
 * action callback for hook_transaction_cluster_alter
 * Alters the original transaction and/or returns a child
 */
function mcapi_supplementary_transaction_action($transactions, $settings) {
  $transaction = reset($transactions);
  if (!in_array($transaction, $settings['transaction_types'])) return;
  $child = array(
    'type' => 'child',
    'state' => $transaction->state
  );
  switch ($settings['mapping']) {
    case 'payerpays':
      $child['payee'] = $settings['otheruser'];
      $child['payer'] = $transaction->payer;
      break;
    case 'payeepays':
      $child['payee'] = $settings['otheruser'];
      $child['payer'] = $transaction->payee;
      break;
    case 'payspayer':
      $child['payee'] = $transaction->payer;
      $child['payer'] = $settings['otheruser'];
      break;
    case 'payspayee':
      $child['payee'] = $transaction->payee;
      $child['payer'] = $settings['otheruser'];
      break;
  }
  foreach ($settings['worth_items']['worths'] as $delta => $item) {
    //if this currency was in the prime transaction, pass it to the calculator
    $calculated = _mcapi_calc($item['quantity'], @$transaction->worth[LANGUAGE_NONE][$delta]['quantity']);
    if (!$calculated) continue;
    $rounded = mcapi_round($calculated, $item['currcode'], $settings['worth_items']['round'] == 'up');
    if (!$rounded) continue;
    //if a quant was returned (and there really should be, from at least one currency), add it to the child
    $child['worth'][$delta] = array(
      'quantity' => $rounded,
      'currcode' => $item['currcode']
    );
  }
  if (isset($child['worth'])) return entity_metadata_create_transaction($child);
  return drupal_set_message(t('Badly configured action failed to create dependent transaction'), 'error');
}

/*
 * Settings form callback for mcapi_pay_someone_action
 * odd that form_state isn't passed
 * this means we can't do form_load_include
 */
function mcapi_supplementary_transaction_action_form($context) {
  $context += array(
    'mapping' => 'payerpays',
    'transaction_types' => array(),
    'otheruser' => 1,
    'worth_items' => array('worths' => array(), 'round' => 'down')
  );
  $form['participants'] = array(
    '#title' => t('Participants'),
    '#description' => t('Determine who will pay whom'),
    '#type' => 'fieldset',
    'mapping' => array(
      '#title' => t('Mapping'),
      '#description' => t('On a node trigger, the payer and payee will both evaluate to the node author'),
      '#type' => 'radios',
      '#options' => array(
        'payerpays' => t('Payer pays other'),
        'payeepays' => t('Payee pays other'),
        'payspayer' => t('Other pays payer'),
        'payspayee' => t('Other pays payee'),
      ),
      '#default_value' => isset($context['mapping']) ? $context['mapping'] : 'payerpays',
    ),
    'otheruser' => array(
      '#title' => t('Other account'),
      '#type' => 'user_chooser_permissions',
      '#args' => array('transact'),
      '#allow_me' => TRUE,
      '#default_value' => isset($context['otheruser']) ? $context['otheruser'] : 1,
      '#weight' => 1,
    )
  );

  $form['transaction_types'] = _transaction_type_selector(@$context['transaction_types'], TRUE);
  mcapi_configure_worth_field($form, $context['worth_items'], FALSE, 'worth_items');
  return $form;
}
/*
 * configuration action submit callback
 */
function mcapi_supplementary_transaction_action_submit($form, $form_state) {
  form_state_values_clean($form_state);
  return $form_state['values'];
}


/**
 * function to generate a fieldset for configuring the worth of an autotransaction
 * presents one currency widget for every currency
 * $form : passed as a reference and nothing is returned
 * $saved : the #tree of default values
 * $dependent : whether or not the auto transaction will depend on on the values from another transaction
 * $fieldset_name : depending on how the settings are saved, this may need to be a unique variable name
 */
function mcapi_configure_worth_field(&$form, $saved, $primary_transaction = TRUE, $fieldset_name = 'worth_items') {
  $currencies = &drupal_static('currencies');
  foreach (@$saved['worths'] as $item) {
    $defaults[$item['currcode']] = $item['quantity'];
  }
  $form[$fieldset_name] = array(
    '#title' => t('Worth'),
    '#type' => 'fieldset',
    '#name' => $fieldset_name,//this helps in the fieldset validation
    '#tree' => TRUE,
    'worths' => array(
      //'#title' => t('Worths'),
      '#type' => 'worths',
      '#default_value' => $defaults,
      '#all' => TRUE,
      '#allow_zero' => FALSE,
        //this is not conifg mode because it's not 'presetting the form its actually setting a value
        //it affects what happens to the zero and null values
      '#required' => TRUE,
    )
  );
  if ($primary_transaction) {
    $form[$fieldset_name]['#description'] = t('What is the fixed value of this transaction?');
  }
  else {
    $form[$fieldset_name]['#description'] = t('The value of the transction may depend on the value of the main transaction.');
    $form[$fieldset_name]['worths']['#description'] = t('Enter a number, a percentage, or a formula using [q] for the transaction quantity.') .' '.
    t('Zero = empty');

    $worths = &$form[$fieldset_name]['worths'];
    foreach (element_children($worths) as $delta) {
      if (!$primary_transaction) {
        //this field will accept a formula, not just a number
        $worths[$delta]['main_quant']['#size'] = 10;
        $worths[$delta]['main_quant']['#maxlength'] = 10;
        $worths[$delta]['main_quant']['#element_validate'] = array('validate_mcapi_configure_worth_formula');
      }
    }
    $form[$fieldset_name]['round'] = array(
      '#title' => t('Rounding'),
      '#type' => 'radios',
      '#options' => array('up' => t('Up'), 'down' => t('Down')),
      '#default_value' => isset($saved['round']) ? $saved['round'] : 'down',
      '#weight' => 1
    );
  }

  if (variable_get('transaction_description_field')) {
    $form['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => @$context['description'],
    );
  }
}


/*
 * element validate callback
 * ensures that the formula for calculating a quantity works
 */
function validate_mcapi_configure_worth_formula(&$element, $form_state) {
  if (empty($element['#value'])) return;
  if (!is_numeric(_mcapi_calc($element['#value'], 100))) {
    form_error($element, t('Invalid formula'));
  }
}

/*
 * calculate a transaction quantity based on a provided formala and input quantity
 * $formula can be
 * number => returns the number as a quantity, regardless of $base_quant
 * percentage of the $base_quant
 * formula using [q] for base_quant
 */
function _mcapi_calc($formula, $base_quant) {
  if (is_numeric($formula)) return $formula;
  $proportion = str_replace('%', '', $formula);
  if (empty($base_quant)) $base_quant = 0;
  if (is_numeric($proportion)) {
    return $base_quant * $proportion/100;
  }
  //$formula = str_replace('/', '//', $formula);
  $equation = str_replace('[q]', $base_quant, $formula) .';';
  $val = eval('return '. $equation);
  if (is_numeric($val)) return $val;
  drupal_set_message(t('Problem with calculation for dependent transaction: @val', array('@val' => $val)));
}
//TODO
//Parse error: syntax error, unexpected '/' in /var/www/drupal7/sites/all/modules/matslats/mutual_credit/mcapi.inc(853) : eval()'d code on line 1


/*
 * helper to get block settings and load whichever user we like
 * may return nothing if no user is determined
 * the possible values of user_source should be made into php constants
 * 0 //the user argument comes from the current path
 * 1 //the user argument is the current user
 */
function _mcapi_block_settings($delta, $currency_access_mode) {
  $settings = variable_get('block_'. $delta, array('currcodes' => array(), 'user_source' => 1));
  if (@$settings['user_source'] == 0) {//Show as part of profile being viewed
    if (arg(0) == 'user') {//and we are viewing a profile...
      $settings['account'] = user_uid_optional_load(arg(1));
    }
    else return;
  }
  else {
    $settings['account'] = $GLOBALS['user'];
  }
  if (empty($settings['currcodes'])) {
    $settings['currcodes'] = array_keys(currencies_load($settings['account']));
  }
  //check that the current user can see the aggregated trading data in this currency
  foreach($settings['currcodes'] as $key => $currcode) {
    if (!currency_access($currency_access_mode, $currcode)) unset($settings['currcodes'][$key]);
  }
  if (empty($settings['currcodes']))return;

  return $settings;
}


/*
 * Calculate the balance changes that this transaction proposes
 * by convention, if the transaction state < 0 it is NOT COUNTED
 * this is only used in tokens, so far, and in mcapi_limits module
 * incoming transaction can be a transaction object with children or an array
 */
function _transactions_diff($transaction) {
  $diff = array();
  //because this can be called before or after the transaction,
  //it might come in as a cluster, or as an object with dependents
  //we need a cluster so we can iterate though
  $cluster = is_array($transaction) ? $transaction :  array_merge(array($transaction) + (array)$transaction->dependents);
  foreach ($cluster as $transfer) {
    foreach ($transfer->worth[LANGUAGE_NONE] as $item) {
      $quant = $transfer->state < -1 ? 0 : $item['quantity'];//i is signature module is enabled, pending transactions count here
      //we can't prepare the array in advance with zeros so += and -= throws notices
      //instead we just build up an array and add them up when we need them
      $diff[$transfer->payer][$item['currcode']][] = -$quant;
      $diff[$transfer->payee][$item['currcode']][] = $quant;
    }
  }
  return $diff;
}

function mcapi_description_field($form, $form_state) {
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


/*
 * default worths element validator
 * and check required
 * clear the $items which are empty
 */
function worths_element_validate(&$element, &$form_state) {
  //we need to drop the empty values to prevent them being written to the database
  //this element contains the worth_element $items, but did not inherit its value from them
  //because, as container, it processes before them. Shame
  //anyway we can retrieve the values, set in form_type_worth_value, from form_state
  //then we filter them reindex the array and send them back to form_state
  $items = drupal_array_get_nested_value($form_state['values'], $element['#array_parents']);

  if ($element['#allow_zero']) {
    foreach ($items as $delta => $item) {
      if (mcapi_field_is_empty($item)) {
        unset($items[$delta]);
      }
    }
  }
  else {
    foreach ($items as $delta => $item) {
      if (empty($item['quantity'])) {
        unset($items[$delta]);
      }
    }
  }
  //renumber the items or field_API will break!
  $items = array_values($items);
  if ($element['#required']) {//note that this checks all the $items together
    if (empty($items)) {
      form_error($element, t('!name field is required.', array('!name' => $element['#title'])));
    }
  }
  //very important for rendering that the $item keys are straight
  form_set_value($element, $items, $form_state);
}

/*
 * element process callback
 * is called once for each $item
 */
function worth_element_process($element, &$form_state) {
  form_load_include($form_state, 'inc', 'mcapi');
  $currencies = &drupal_static('currencies');
  $main_quant = '';
  $quantity = $element['#default_value']['quantity'];
  if (empty($element['#currcodes'])) {
    //best not rely on this, supply your own currencies that the user is permitted to use.
    //this defaults to all
    $element['#currcodes'] = array_keys($currencies);
  }
  $element['currcode'] = currency_picker_element($element['#currcodes'], @$element['#default_value']['currcode']);
  $currency = currency_load($element['currcode']['#value'] ? $element['currcode']['#value'] : $element['currcode']['#default_value']);
  $element['#title'] = $currency->human_name;
  $element['#title_display'] = 'attribute';

  if (count($element['#currcodes']) == '1') {//and the currency chooser will be hidden
    $use_div_subelement = in_array($currency->display['divisions'], array(CURRENCY_DIVISION_MODE_CUSTOM, CURRENCY_DIVISION_MODE_CENTS_FIELD));
  }

  //work out the defaults for each subfield
  //null is not presumed to be zero, and is preserved
  if (!empty($use_div_subelement)) {
    list($main_quant, $div_quant) = currency_explode($quantity);
  }
  else {
    $main_quant = $quantity;
  }
  //in normal mode, show a blank field where the default value is 0
  if (empty($element['#allow_zero']) && $main_quant == '0') {
    $main_quant = '';
  }
  //while we return a float, the widget may want to show two fields.
  //in any case we show a main field, and an optional division field
  $element['main_quant'] = array(
    '#title' => t('Quantity'),//this is only really needed for errors, and maybe not even then
    '#title_display' => 'attribute',
    '#type' => 'textfield',
    '#default_value' => strlen($main_quant) ? $main_quant : '',
    '#size' => 4,
    '#maxlength' => 6,
    '#theme_wrappers' => array()//to prevent the default form_element wrapper
  );

  //we only show the division field if the currency requires it
  if (isset($div_quant)) {//that means $currency is also set

    if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM) {
      $element['div_quant'] = array(
        '#title' => t('Fraction'),
        '#title_display' => 'attribute',
        '#type' => 'select',
        '#options' => $currency->display['divisions_allowed'],
        '#default_value' => $div_quant
      );
    }
    else {//must be CURRENCY_DIVISION_MODE_CENTS_FIELD
      $element['div_quant'] = array(
        '#title' => t('Cents'),
        '#title_display' => 'attribute',
        '#type' => 'textfield',
        '#size' => 2,
        '#maxlength' => 2,
        '#default_value' => $div_quant,
        '#theme_wrappers' => array()//to prevent the default form_element wrapper
      );
    }
  }

  return $element;
}

/**
 * element validation callback
 * check the centiles value is valid
 * check that the $quantity is a number
 */
function worth_element_validate(&$element, &$form_state, $form) {
  $quant = $element['#value']['quantity'];

  if (!is_numeric($quant) && !$element['#allow_zero']) {
    form_error($element, t('@fieldname must be numeric', array('@fieldname' => $element['#title'])));
  }
  if ($quant < 0) {
    form_error($element, t('Negative values not allowed'));
  }

  $currencies = &drupal_static('currencies');
  if (!isset($currencies[$element['#value']['currcode']])) {
    form_error($element, t('This currency does not exist: @currcode', array('@currcode' => $element['#value']['currcode'])));
  }
  if ($division = $quant - intval($quant)) {
    $currency = currency_load($element['#value']['currcode']);
    //check that divisions match allowed values for the currency
    if (count($currency->display['divisions_allowed']) && !isset($currency->display['divisions_allowed'][$division*100])) {
      form_error($element, t('@division is not a permitted division of currency @name', array('@division' => $division, '@name' => $currency->human_name)));
    }
  }
}




/*
 * Default implementation of theme 'worth_field'
 * Converts the quantity into tokens and replaces them into the currency display format
 * $variables should have keys
 * 'quantity'
 * 'currcode'
 */
function theme_worth_item(&$variables) {
  $currency = currency_load($variables['currcode']);
  list($replacements['@integer'], $replacements['@subdivision'], $replacements['@minus']) = currency_explode($variables['quantity']);
  switch($currency->display['divisions']) {
    case CURRENCY_DIVISION_MODE_CUSTOM :
      $replacements['@subdivision'] = @$currency->display['divisions_allowed'][intval($replacements['@subdivision'])];
      break;
    case CURRENCY_DIVISION_MODE_CENTS_FIELD:
    case CURRENCY_DIVISION_MODE_CENTS_INLINE:
      $replacements['@subdivision'] = str_pad($replacements['@subdivision'], 2, STR_PAD_LEFT);
      break;
  }
  //I don't know how to account for different languages in number format
  $replacements['@integer'] = number_format(floatval($replacements['@integer']));
  //add the extra space to save writing a multiple rendering function
  return strtr($currency->display['format'], $replacements) .' ';
}

/*
 * default theme implementation for callback 'worth_element'
 * called once per $item
 */
function theme_worth_element($variables) {
  $element = &$variables['element'];
  $currcode = drupal_render($element['currcode']);
  if ($element['currcode']['#type'] == 'hidden') {
    $vars = array(
      '@minus' => '',//no negative values in a form widget
      '@integer' => drupal_render($element['main_quant']),
      '@subdivision' => !empty($element['div_quant']) ? drupal_render($element['div_quant']) : '',
    );
    //output starts with the currency field, whether hidden or showing
    $quantity = strtr(currency_load($element['#value']['currcode'])->display['format'], $vars);
  }
  else {//this will be a select widget and a textfield
    $quantity = drupal_render($element['main_quant']);
  }
  $element['#attributes']['class'][] = "worth-widget";
  return '<div ' . drupal_attributes($element['#attributes']) . '>'. $currcode . $quantity .'</div>';
}
/*
function theme_worths_element($variables) {
  $element = &$variables['element'];
  $children = drupal_render_children($element);
  $element['#attributes']['class'][] = "worths-widget";
  return '<div ' . drupal_attributes($element['#attributes']) . '>'.$children .'</div>';
}
*/


/*
 * default implementation of theme callback
 */
function theme_transaction_totals(&$variables) {
  foreach ($variables['totals'] as $currcode => $totals) {
    $balance = theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->balance));
    $income = theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->gross_in));
    $output = '<div class="balance">'. $balance ."</div>";
    $output .= '<div class="gross-in">'.t('Income: !quant', array('!quant' => $income)) ."</div>";
    $output .= '<div class="count">'.t('Transactions: @count', array('@count' => $totals->count)) ."</div>";
    $output .= '<div class="volume">'.t('Volume: @count', array('@count' => $totals->volume)) ."</div>";
    $outputs[] = "<div class = \"transaction-totals $currcode\">$output</div>";
  }
  return implode("<br />", $outputs);
}