<?php

/*
 * this is called by the hook mcapi_entity_info
 */
function _mcapi_entity_info() {
  $entities['transaction'] = array(
    'label' => t('Transaction'),
    'entity class' => 'transaction',
    'controller class' => variable_get('mcapi_controller', 'mcapi') .'EntityController',
    'base table' => 'mcapi_transactions',
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
          'path' => 'admin/accounting',
          'access arguments' => array('manage all transactions')
        )
      )
    ),
    'view modes' => array(
      'certificate' => array(
        'label' => t("A transaction certificate"),
        'custom settings' => FALSE,
      ),
    ),
    'load hook' => 'mcapi_xaction_load',//this isn't used but must avoid conflict with commerce module which has transaction_load
    //entity API properties
    //generally we want the entity controller only to contain the read/write functions
    //so it is easy to replace
    'label callback' => 'transaction_label',
    'access callback' => 'transaction_operation_access',
    'save callback' => 'transaction_cluster_create',
    'view callback' => 'transaction_view',
    //'form callback' => 'transaction_form',//implement this later perhaps
    'views controller class' => 'transactionDefaultViewsController',
    'rules controller class' => 'TransactionRulesController'
    //'access_callback'

    /* I don't know how to make this work, or if its necessary
    'exportable' => TRUE,
    'export' => array(
      'default hook' => 'transaction_export_callback_test'
    ),
     */
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


function _mcapi_menu() {
  $items['admin/accounting'] = array(
    'title' => 'Accounting',
    'description' => "Manage all aspects of your community's money system",
    'page callback' => 'mcapi_accounting',
    'access arguments' => array('manage all transactions'),
    'file' => 'mcapi.admin.inc',
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
    'file path' => drupal_get_path('module', 'mcapi')
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
    'file' => 'mcapi.admin.inc',
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
  $transaction_argument_position = 1;
  $items['transaction/%transaction'] = array(
    'title' => 'Transaction certificate',
    'title callback' => 'entity_label',
    'title arguments' => array('transaction', $transaction_argument_position),
    // The page callback also invokes drupal_set_title() in case
    // the menu router's title is overridden by a menu link.
    'page callback' => 'transaction_view',
    'page arguments' => array($transaction_argument_position, 'certificate'),
    'access callback' => 'entity_access',
    'access arguments' => array('view', 'transaction', $transaction_argument_position),
    'type' => MENU_CALLBACK,
  );
  $items['transaction/%transaction/view'] = array(
    'title' => 'View',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );

  foreach (transaction_operations(NULL, FALSE, FALSE) as $op => $info) {
    $items["transaction/%transaction/$op"] = array(
      'title' => $info['imperative'],
      'description' => $info['description'],
      'page callback' => 'drupal_get_form',
      'page arguments' => array('transaction_operation_form', $op, $transaction_argument_position),
      'access callback' => 'entity_access',
      'access arguments' => array($op, 'transaction', $transaction_argument_position),
      'weight' => 1,
      'type' => MENU_LOCAL_ACTION,
    );
    if (isset($info['file_extension'])) {
      $items["transaction/%transaction/$op"]['file'] = $info['module'] .'.'. $info['file_extension'];
      $items["transaction/%transaction/$op"]['file path'] = drupal_get_path('module', $info['module']);
    }
  }
  return $items;
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
 * drupal_get_form callback for menu item admin/accounting/misc
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
  /*
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
   *
   */
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

/*
 * ctools plugin callback for editing a currency, determined by the ctools export_plugin
 * The form_id is actually ctools_export_ui_edit_item_form
 */
function mcapi_currency_form(&$form, &$form_state){
  form_load_include($form_state, 'admin.inc', 'mcapi');
  $currency = isset($form_state['values']) ? (object)$form_state['values'] :
    ($form_state['item']->data ? $form_state['item']->data : default_cur());
  //copy some settings from the default currency
  if (isset($currency->human_name)) {
    drupal_set_title( !empty($currency->human_name) ? $title = t("Edit currency '@name'", array('@name' => $currency->human_name)) : t('Declare currency') );
  }
  if (isset($form_state['values']['uid'])) {
    $currency->uid = $form_state['values']['uid'];
  }

  $form['#tree'] = TRUE;
  $form['info']['currcode']['#maxlength'] = 8;
  $form['human_name'] = array(
    '#title' => t('Name of currency'),
    '#description' => t('Use the plural'),
    '#type' => 'textfield',
    '#default_value' => $currency->human_name,
    '#weight' => -1,
  );
  //nice to do a live machine name preview here, like views does, but I could work out how!
  //http://api.drupal.org/api/drupal/includes%21form.inc/function/form_process_machine_name/7

  $form['uid'] = array(
    '#title' => t('Declared by'),
    '#type' => 'user_chooser_few',
    '#callback' => 'user_chooser_segment_permissions',
    '#args' => array('manage all transactions'),
    '#default_value' => property_exists($currency, 'uid') ? $currency->uid : $GLOBALS['user']->name,
    '#multiple' => FALSE,
    '#required' => TRUE,
    '#weight' => 3,
  );
  $form['reservoir'] = array(
    '#title' => t('Reservoir account'),
    '#description' => t('Account used for issuing and taxing'),
    '#type' => 'user_chooser_few',
    '#callback' => 'user_chooser_segment_perms',
    '#args' => array('transact'),
    '#default_value' => property_exists($currency, 'reservoir') ? $currency->reservoir : 1,
    '#multiple' => FALSE,
    '#weight' => 4
  );

  $form['display'] = array(
    '#title' => t('Appearance'),
    '#type' => 'fieldset',
    '#weight' => 5
  );
  $form['display']['format'] = array(
    '#title' => t('Display format'),
    '#description' => t('Write an expression to control the display of the currency using html, css and the following tokens:') .'@minus, @integer, @subdivision.' .' '.
       t('For example, !a shows as !b', array('!a' => '<em>@minus@integer:@subdivision AUS</em>', '!b' => '<em>-99:99 AUS</em>')),
    '#type' => 'textfield',
    '#default_value' => $currency->display['format'],
    '#element_validate' => array('mcapi_currency_validate_format'),
    '#required' => TRUE,
    '#weight' => 1
  );
  $form['display']['divisions'] = array(
    '#title' => t('Subdivisions'),
    '#type' => 'select',
    '#options' => array(
      CURRENCY_DIVISION_MODE_NONE => t('Integer values only'),
      CURRENCY_DIVISION_MODE_CENTS_INLINE => t("Cents in same field, delimited by '.'"),
      CURRENCY_DIVISION_MODE_CENTS_FIELD => t('Cents in separate field'),
      CURRENCY_DIVISION_MODE_CUSTOM => t('Allowed subdivisions, below')
    ),
    '#default_value' => $currency->display['divisions'],
    '#weight' => 2
  );
  $form['display']['divisions_setting'] = array(
    '#title' => t('Allowed subdivisions'),
    '#description' => t('On each line put "hundredths| visible text". The visible text replaces the @subdivision in the display format, above.'),
    '#type' => 'textarea',
    '#cols' => 60,
    '#rows' => 4,
    '#resizable' => FALSE,
    '#default_value' => isset($currency->display['divisions_setting']) ? $currency->display['divisions_setting'] : "0|/.\n25|1/4\n50|1/2\n75|3/4",
    '#element_validate' => array('mcapi_currency_validate_divisions'),
    '#weight' => 3
  );

  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 10,
  );
  $form['currency_access'] = array(
    '#title' => 'Currency access',
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
    '#weight' => -1
  );
  $weight = 0;


  foreach (module_implements('permission') as $module) {
    $function = $module .'_permission';
    foreach ($function() as $perm => $info) {
      $options[$module][$perm] = strip_tags($info['title']);
    }
  }
  $form['currency_access']['membership'] = array(
    '#title' => t('Use the currency'),
    '#description' => t('Determine which users are permitted to use this currency'),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => @$currency->currency_access['membership'] ? $currency->currency_access['membership'] : 'transact',
    '#weight' => $weight++,
  );
  $form['currency_access']['user_aggregated_view'] = array(
    '#title' => t('View aggregated user transaction data'),
    '#description' => t("Such as users' balances, gross income, number of transactions"),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => @$currency->currency_access['user_aggregated_view'] ? $currency->currency_access['user_aggregated_view'] : 'transact',
    '#weight' => $weight++,
  );
  $form['currency_access']['currency_aggregated_view'] = array(
    '#title' => t('View aggregated system data'),
    '#description' => t('Look at currency usage stats stripped of personal information'),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => @$currency->currency_access['currency_aggregated_view'] ? $currency->currency_access['currency_aggregated_view'] : 'transact',
    '#weight' => $weight++,
  );

  static $i = 0;
  static $j = 0;
  $form['transaction_view_access'] = array(
    '#title' => t('Privacy'),
    '#description' => t('Determine who can view transactions in each state.') .' '. t('Any the checked conditions must return TRUE'),
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
  );
  $access_callbacks = module_invoke_all('transaction_access_callbacks');
  foreach (mcapi_get_states('#full') as $constant => $state) {
    $states = isset($currency->view) ? $currency->view : array();
    $form['transaction_view_access'][$constant] = array(
      '#title' => t("Transactions in state '@state'", array('@state' => $state['name'])),
      '#description' => $state['description'],
      '#type' => 'checkboxes',
      '#options' => $access_callbacks,
      '#default_value' => isset($currency->transaction_view_access[$constant]) ? $currency->transaction_view_access[$constant] : array(),
      '#weight' => $j++,
    );
  }
  $form['transaction_operation_access'] = array(
    '#title' => t('Transaction operations'),
    '#description' => t('Determine who can do what to transactions') .'. '. t('Any of the checked conditions must return TRUE'),
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
  );

  foreach (module_invoke_all('transaction_operations') as $callback => $info) {
    $form['transaction_operation_access'][$callback] = array(
      '#title' => $info['description'],
      '#description' => @$info['description'],
      '#type' => 'checkboxes',
      '#options' => $access_callbacks,
      '#default_value' => isset($currency->transaction_operation_access[$callback]) ? $currency->transaction_operation_access[$callback] : array(),
      '#weight' => $j++,
    );
  }
  $form['pay_zero'] = array(
    '#title' => t('Record zero payments'),
    '#description' => t('Store payments of 0 or empty strings in the database'),
    '#type' => 'checkbox',
    '#default_value' => $currency->pay_zero,
    '#weight' => 12
  );

  if (isset($currency->currcode)) {
    $form['buttons']['submit']['#value'] = t('Update');
  }
}

//currency form validate callback
function mcapi_currency_validate_format(&$element, $form_state) {
  if (!strpos($element['#value'], '@integer')) {
    form_error($element, t("Currency format must contain token '@integer'"));
  }
  //we also need to check that the format field contains the divisions token
  if ($form_state['values']['display']['divisions'] != CURRENCY_DIVISION_MODE_NONE && !strpos($element['#value'], '@subdivision')) {
    form_error($element, t("Currency format must contain token '@subdivision'"));
  }
}

//currency form validate callback
function mcapi_currency_validate_divisions(&$element, $form_state) {
  if ($form_state['values']['display']['divisions'] != CURRENCY_DIVISION_MODE_CUSTOM) return;
  $allowed = $element['#value'];
  $lines = explode("\n", $allowed);
  if (count($lines) < 2) {
    form_error($element, t("There should be at least two lines in field '@fieldname'", array('@fieldname' => $element['#title'])));
  }
  foreach ($lines as $line) {
    if (strpos($line, '|') === FALSE) {
      form_error($element, t('line "@val" should contain a pipe character, |', array('@val' => $line)));
    }
    $vals = explode('|', $line);
    if (!is_numeric($vals[0]) || !strlen($lines[0]) || $vals[1] != check_plain($vals[1]) || !strlen($vals[1] || !is_integer($vals[1]))) {
      form_error($element,
        t("'@val' should be an integer from  0 to 99, followed directly by a pipe, |, followed directly by a word or phrase with no unusual characters",
          array('@val' => $line)
        )
      );
    }
  }
}

/*
 * Validate callback for transaction form 'currcode' element
 */
function mcapi_validate_currcode(&$element, $form_state) {
  if (isset($element['#value']) && $element['#value']) {
    $currency = currency_load($element['#value']);
    if (!is_object($currency)) { //you would also check here if the user had permission to use this currency
      form_error($element, t('Invalid currency specified: @num', array('@num' => $element['#value'])));
    }
  }
}
/*
 * menu access callback
 * access to the ctools currency edit page
 */
function currency_edit_access($currency) {
  return user_access('configure all currencies') || $currency->uid == $GLOBALS['user']->uid;
}
/*
 * load the currencies from ctools and alter them before they get used
 */
function currencies_load_all() {
  ctools_include('export');
  $currencies = array();
  $functions = array_keys(module_invoke_all('transaction_access_callbacks'));
  $all = ctools_export_crud_load_all('mcapi_currencies');
  //unpack them - this is a bit of a shortcut, but ctools takes you right round the park
  foreach($all as $currcode => $currency) {
    if (!empty($currency->disabled)) continue;
    $data = &$currency->data;
    //convert the divisions_allowed into an array
    $divisions = array();
    if ($data->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM) {
      foreach(explode("\n", $data->display['divisions_setting']) as $line) {
        list($cent, $display) = explode('|', $line);
        $divisions[$cent] = trim($display);
      }
    }
    $data->display['divisions_allowed'] = $divisions;
    //this is a good moment to check that all the currencies for all the operations, for all the states, have access callbacks
    $accesses = array(
      'transaction_view_access' => 'Privacy',
      'transaction_operation_access' => 'Transaction operations',
      'currency_access' => 'Currency Access'
    );
    foreach ($accesses as $prop => $name) {
      if (property_exists($data, $prop)) continue;
      drupal_set_message(
        "Resave currency $data->human_name, paying attention to $name : ". l(t('edit'), 'admin/accounting/currencies/list/'.$currcode.'/edit'),
        'warning'
      );
    }
    $currencies[$currcode] = $currency;
  }
  return $currencies;
}

//load the default currency and clear some fields, so it can populate the edit form with some discrete defaults
function default_cur() {
  $currency = ctools_export_crud_load('mcapi_currencies', DEFAULT_CURRENCY)->data;
  $currency->human_name = '';
  $currency->display['format'] = '@minus@integer @subdivision';
  return $currency;
}

/*
 * Validate callback for mcapi_currency_form
 * declared in plugins/export/mcapi_ui
 * set the $form_state['item'] for ctools to save
 */
function mcapi_currency_form_validate($form, &$form_state){

}
/*
 * Submit callback for mcapi_currency_form
 * declared in plugins/export/mcapi_ui
 * set the $form_state['item'] for ctools to save
 */
function mcapi_currency_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);
  //things got a little out of kilter here - this shouldn't be necessary
  $form_state['item']->currcode = $form_state['values']['info']['currcode'];
  $form_state['item']->data = (object)$form_state['values'];
  cache_clear_all('currencies', 'cache');
}

function _mcapi_action_info() {
  return array(
    'mcapi_dependent_transaction_action' => array(
      'type' => 'transactions',
      'label' => t('Make a dependent payment'),
      'configurable' => TRUE,
      'triggers' => array('transaction_cluster_alter'),
    ),
    'mcapi_transaction_mail_action' => array(
      'type' => 'transactions',
      'label' => t('Completed transaction notification'),
      'description' => t('Mail sent to the participants when the transaction completes'),
      'configurable' => TRUE,
      'triggers' => array('transaction_state'),
    ),
    'mcapi_transaction_entity_uid' => array(
      'type' => 'transactions',
      'label' => t('Create transaction with entity owner'),
      'description' => t('Pay or charge the user or node owner.'),
      'configurable' => TRUE,
      'triggers' => array(//all the core triggers, except taxonomy
        'node_presave', 'node_insert', 'node_update', 'node_delete', 'node_view',
        'comment_presave', 'comment_insert', 'comment_update', 'comment_delete', 'comment_view',
        'user_insert', 'user_update', 'user_delete', 'user_login', 'user_logout',  'user_view',
      ),
    )
  );
}


function mcapi_mixed_transactions($form) {
  $form['field']['mcapi_mixed_transactions'] = array(
    '#title' => t('Currencies per transaction'),
    '#type' => 'radios',
    '#options' => array(
      0 => t('One currency per transaction'),
      1 => t('Many currencies per transaction')
    ),
    '#default_value' => variable_get('mcapi_mixed_transactions', 0)
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit')
  );
  return $form;
}
function mcapi_mixed_transactions_submit($form, &$form_state) {
  $val = &$form_state['values']['mcapi_mixed_transactions'];
  variable_set('mcapi_mixed_transactions', $val);
  $field = field_read_field('worth');
  $currencies = &drupal_static('currencies');
  $field['cardinality'] = $val ? count($currencies) : 1;
  field_update_field($field);
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
      'uidpays' => t('User pays account 1'),
      'paysuid' => t('Account 1 pays user')
    ),
    '#default_value' => isset($context['mapping']) ? $context['mapping'] : 'payerpays',
  );

  $currencies = &drupal_static('currencies');
  $defaults = array();
  foreach (@$context['worth_items']['worths'] as $item) {
    $defaults[$item['currcode']] = $item['quantity'];
  }
  $form['worth_items'] = array(
    '#title' => t('Worth'),
    '#type' => 'fieldset',
    '#name' => 'worth_items',//this helps in the fieldset validation
    '#description' => t('What is the fixed value of this transaction?') .' '. t("this value will be overwritten if the passed entity has the 'worth' field"),
    '#tree' => TRUE,
    'worths' => array(
      //'#title' => t('Worths'),
      '#type' => 'worths',
      '#default_value' => $defaults,
      '#preset' => TRUE,//ensures that all currencies are rendered
    )
  );
  $form[$fieldset_name]['round'] = array(
    '#title' => t('Rounding'),
    '#type' => 'radios',
    '#options' => array('up' => t('Up'), 'down' => t('Down')),
    '#default_value' => isset($context['worth_items']['round']) ? $context['worth_items']['round'] : 'down',
    '#weight' => 1
  );

  if (variable_get('transaction_description_field')) {
    $form[$fieldset_name]['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => @$context['worth_items']['description'],
    );
  }
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
 * Settings form callback for mcapi_pay_someone_action
 * odd that form_state isn't passed
 * this means we can't do form_load_include
 */
function mcapi_dependent_transaction_action_form($context) {
  drupal_set_message('This tool is a bit crude but it will have to do until someone can figure out how to do it with rules');
  drupal_set_message('This action ONLY goes on one the hook, and adds a dependent transaction to the cluster. with the same serial number');
  drupal_set_message('It will be easy to make this action more sophisticated');
  //set the defaults
  $context += array(
    'conditions' => array(
      'transaction_types' => array('default')
    ),
    'presets' => array(
      'participants' => array(
        'mapping' => 'payerpays',
        'otheruser' => 1
      ),
      'worth_items' => array(
        'worths' => array(),
        'round' => 'down'
      )
    ),
  );
  $form['#tree'] = TRUE;
  $form['conditions'] = array(
    '#title' => t('Conditions'),
    '#description' => t('A dependent transaction will only be created when the main transaction meets these conditions'),
    '#type' => 'fieldset',
    '#weight' => -1,
    'transaction_types' => _transaction_type_selector(@$context['conditions']['transaction_types'], TRUE)
    //more could be added, including stuff unconnected to the transaction, like whether there is an r in the month
  );

  $form['presets'] = array(
    '#title' => t('Presets'),
    '#description' => t('Specify the properties of the dependent transaction') .' '.t("It will have the type 'dependent'."),
    '#type' => 'fieldset',
    '#weight' => 0,
    'participants' => array(
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
        '#default_value' => isset($context['presets']['participants']['mapping']) ? $context['presets']['participants']['mapping'] : array('payerpays'),
      ),
      'otheruser' => array(
        '#title' => t('Other account'),
        '#type' => 'user_chooser_permissions',
        '#args' => array('transact'),
        '#default_value' => isset($context['presets']['participants']['otheruser']) ? $context['presets']['participants']['otheruser'] : 1,
        '#weight' => 1,
      )
    ),
    'worth_items' => array(
      '#title' => t('Worth'),
      '#description' => t('The value of the transction may depend on the value of the main transaction.'),
      '#type' => 'fieldset',
      '#name' => 'worth_items',//this helps in the fieldset validation
      '#tree' => TRUE,
      'worths' => array(
        //'#title' => t('Worths'),
        '#description' => t('Enter a number, a percentage, or a formula using [q] for the transaction quantity.'),
        '#type' => 'worths',
        '#default_value' => isset($context['presets']['worth_items']['worths']) ? _worthitems_to_worths($context['presets']['worth_items']['worths']) : array(),
        '#calc' => TRUE,//allow a formula
        '#preset' => TRUE, //show all currencies
        //it affects what happens to the zero and null values
        '#after_build' => array('mcapi_configure_worth_accept_formula')
      ),
      'round' => array(
        '#title' => t('Rounding'),
        '#type' => 'radios',
        '#options' => array('up' => t('Up'), 'down' => t('Down')),
        '#default_value' => isset($context['presets']['worth_items']['round']) ? $context['presets']['worth_items']['round'] : 'down',
        '#weight' => 1
      )
    )
  );

  if (variable_get('transaction_description_field')) {
    $form['presets']['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => @$context['presets']['description'],
    );
  }

  return $form;
}

function mcapi_configure_worth_accept_formula($element) {
  foreach (element_children($element) as $delta) {
    //this field will accept a formula, not just a number
    $element[$delta]['main_quant']['#size'] = 10;
    $element[$delta]['main_quant']['#maxlength'] = 10;
    $element[$delta]['main_quant']['#element_validate'] = array('validate_mcapi_configure_worth_formula');
  }
  return $element;
}

/*
 * configuration action submit callback
 */
function mcapi_dependent_transaction_action_submit($form, $form_state) {
  form_state_values_clean($form_state);
  return $form_state['values'];
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

function _worthitems_to_worths(array $worthitems) {
  $worths = array();
  foreach ($worthitems as $worth_item) {
    $worths[$worth_item['currcode']] = $worth_item['quantity'];
  }
  return $worths;
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