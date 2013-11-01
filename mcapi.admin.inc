<?php

use Drupal\Core\Template\Attribute;

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
      //'name' => 'serial',//name is required by entity API module for exporting if exportable were TRUE
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
    //this isn't implemented but must be declared here to avoid conflict with commerce module
    //which has a function transaction_load
    'load hook' => 'mcapi_xaction_load',
    //entity API properties
    //generally we want the entity controller only to contain the read/write functions
    //so it is easy to replace
    'save callback' => 'transaction_cluster_create',//not advised to use with existing transactions
    'label callback' => 'transaction_label',//doesn't belong in the controller
    'access callback' => 'transaction_operation_access',//doesn't belong in the controller
    'view callback' => 'transaction_view',//doesn't belong in the controller
    //'creation callback' => not needed because controller implements create()
    //'form callback' => 'transaction_form',//implement this later perhaps
    'views controller class' => 'transactionDefaultViewsController',
    'rules controller class' => 'TransactionRulesController',
    'entity cache' => FALSE //because we would need to write special methods for this

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
    'title' => t('Accounting'),
    'description' => t("Manage all aspects of your community's money system"),
    'weight' => 2,
    'route_name' => 'mcapi.admin',
  );

  $items['admin/accounting/config'] = array(
    'title' => 'Configuration',
    'type' => MENU_VISIBLE_IN_BREADCRUMB,
  );
  $items['admin/accounting/misc'] = array(
    'title' => t('Misc settings'),
    'route_name' => 'mcapi.admin_misc',
  );
  $items['admin/accounting/currencies'] = array(
    'title' => t('Currencies'),
    'description' => t('Each currency implies a set of values and a community of users who economic fortunes are intertwined. Most communities will want only one currency, but if you are hosting many communities in one drupal instance, or if there is an appetite for experimentation, different currencies can be provided. User can access many potentially very different currencies. Mixed payments are also possible, but this is pushing the boundaries of the software.'),
    'route_name' => 'mcapi.admin_currency_list',
  );
  $items['admin/accounting/workflow'] = array(
    'title' => "Workflow",
    'description' => "Configuration around transaction states, types and operations",
    'weight' => 2,
    'route_name' => 'mcapi.workflow',
  );
  /*
  $items['transact/log'] = array(
    'title' => "Record transaction",
    'description' => "Log a transaction in the database",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('transaction_default_form'),
    //we use this permission but it's possible to create a paradox since each currency has its own permission
    //if we knew what currency we would use callback 'access' and arg 'membership'
    'access arguments' => array('transact'),
    'file' => 'mcapi.inc',
    'file path' => drupal_get_path('module', 'mcapi')
  );
  $items['admin/accounting/currencies/%currency/edit'] = array(
    'title' => "Configure currency",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('currency_form', 3),
    'access callback' => 'currency_edit_access',
    'access arguments' => array(3),
    'type' => MENU_CALLBACK,
  );
  $operations = transaction_operations(FALSE, FALSE);
  //this item is for a the special transaction operation, 'view'
  $items['transaction/%transaction'] = array(
    'title callback' => 'entity_label',
    'title arguments' => array('transaction', 1),
    // The page callback also invokes drupal_set_title() in case
    // the menu router's title is overridden by a menu link.
    'page callback' => 'transaction_view',
    'page arguments' => array(1, 'certificate'),
    //this is quicker than using entity_access
    //note that this function includes mcapi.inc itself
    'access callback' => 'entity_access',
    'access arguments' => array('mcapi_view', 'transaction', 1),
    'type' => MENU_CALLBACK,
  );
  $items['transaction/%transaction/view'] = array(
    'title' => 'View',
    'type' => MENU_DEFAULT_LOCAL_TASK,
  );

  //these are all the other exposed operations
  $items["transaction/%transaction/nojs/%"] = array(
    'page callback' => 'mcapi_get_op_form',
    'page arguments' => array(3, 1),
    'access callback' => 'entity_access',
    'access arguments' => array(3, 'transaction', 1),
    'type' => MENU_CALLBACK,
    'delivery callback' => 'drupal_deliver_html_page',
    'file' => 'mcapi.inc'
  );
  $items["transaction/%transaction/ajax/%"] = array(
    'page callback' => 'mcapi_get_op_form',
    'page arguments' => array(3, 1, 2),
    'access callback' => 'entity_access',
    'access arguments' => array(3, 'transaction', 1),
    'type' => MENU_CALLBACK,
    'delivery callback' => 'ajax_deliver',
    'file' => 'mcapi.inc'
  );
  if (isset($info['filepath'])) {
    $items["transaction/%transaction/$ajax/%"] += array(
      'file' => $info['filepath'],
      'file path' => drupal_get_path('module', $info['module'])
    );
  }

  if ($aid = db_query("SELECT aid FROM {actions} WHERE callback = 'mcapi_transaction_mail_action'")->fetchfield()) {
    $items['admin/accounting/mail_action'] = array(
      'title' => 'Completion mails',
      'description' => 'configure mail notifications sent when the transaction completes',
      'page callback' => 'drupal_get_form',
      'page arguments' => array('system_actions_configure', $aid),
      'access arguments' => array('administer actions'),
      'weight' => 8,
      'file' => 'system.admin.inc',
      'file path' => drupal_get_path('module', 'system')
    );
  }
  */
  return $items;
}

/*
 * implements hook_menu_alter
 */
function mcapi_menu_alter(&$items) {
  $items['admin/accounting/fields']['title'] = 'Transactions FieldAPI';
}

function mcapi_workflow_help() {
  $renderable = array(
    '#attached' => array(
      'css' => array(
        drupal_get_path('module', 'mcapi') . '/css/mcapi.workflow.css',
      ),
    ),
    '#type' => 'container',
    '#attributes' => new Attribute(array('id' => 'mcapi-workflow-container', 'class' => 'clearfix')),
    'states' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction states, from !hook', array('!hook' => "\n<br />hook_mcapi_info_states()")),
      '#header' => array(t('Name'), t('Description')),
      '#attributes' => new Attribute(array('class' => array('mcapi-workflow-states'))),
    ),
    'types' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction types, from !hook.', array('!hook' => "\n<br />hook_mcapi_info_types()")),
      '#header' => array(t('machine name')),
      '#attributes' => new Attribute(array('class' => array('mcapi-workflow-types'))),
    ),
    'ops' => array(
      '#theme' => 'table',
      '#caption' => t('Operations, from !hook.', array('!hook' => "\n<br />hook_transaction_operations()")),
      '#header' => array(t('Name'), t('Description')),
      '#attributes' => new Attribute(array('class' => array('mcapi-workflow-operations'))),
    ),
  );
  foreach (mcapi_get_states('#full') as $name => $info) {
    $renderable['states']['#rows'][$name]['name'] = $info['name'];
    $renderable['states']['#rows'][$name]['dscription'] = $info['description'];
  }
  foreach (mcapi_get_types() as $type) {
    $renderable['types']['#rows'][$type]['name'] = $type;
  }
  foreach (transaction_operations('1', '0') as $op => $info) {
    if ($op == 'mcapi_view')continue;
    $renderable['ops']['#rows'][$op] = array(
      'name' => $info['title'],
      'description' =>  $info['description'],
      //'form' => empty($info['form callback']) ? t('No'): t('Yes'),
    );
  }
  return render($renderable);
}

/*
 * menu access callback
 * access to the ctools currency edit page
 */
function currency_edit_access($currency) {
  return user_access('configure all currencies') || $currency->uid == \Drupal::currentUser()->id();
}
/*
 * load the currencies from ctools and alter them before they get used
 */
function currencies_load_all() {
  $currencies = array();
  $functions = array_keys(module_invoke_all('transaction_access_callbacks'));
  $all = entity_load_multiple('mcapi_currencies');
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
    //TEMP
    //this is a good moment to check that all the currencies for all the operations, for all the states, have access callbacks
    $accesses = array(
      'view_transaction_states' => t('Privacy'),
      'access_operations' => t('Transaction operations'),
      'access' => t('Currency Access')
    );
    foreach ($accesses as $prop => $name) {
      if (property_exists($data, $prop)) continue;
      $message = t("Resave currency @name, paying attention to '@prop'", array('@name' => $data->name, '@prop' => $prop));
      drupal_set_message($message, 'warning');
    }
    $currencies[$currcode] = $currency;
  }
  return $currencies;
}

/*
 * Submit callback for mcapi_currency_form
 * declared in plugins/export/mcapi_ui
 * set the $form_state['item'] for ctools to save
 */
function mcapi_currency_form_submit($form, &$form_state) {
  form_state_values_clean($form_state);
  //reduce the size of this object which is always in memory
  foreach ($form_state['values']['access_operations'] as $key => $permissions) {
    $form_state['values']['access_operations'][$key] = array_filter($permissions);
  }
  //things got a little out of kilter here - this shouldn't be necessary
  $form_state['item']->currcode = $form_state['values']['info']['currcode'];
  $form_state['item']->data = (object)$form_state['values'];
  cache()->invalidate('currencies');
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
      'description' => t('Pay or charge the node or comment owner, or the user'),
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
    //something is setting this value to empty during upgrade I think
    '#default_value' => intval(variable_get('mcapi_mixed_transactions', TRUE))
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
  $currencies = currencies_load();
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
      'uidpays' => t('User pays reservoir account'),
      'paysuid' => t('Reservoir account pays user')
    ),
    '#default_value' => isset($context['mapping']) ? $context['mapping'] : 'payerpays',
  );

  $currencies = currencies_load();
  $defaults = array();
  foreach (@$context['worth_items']['worths'] as $item) {
    $defaults[$item['currcode']] = $item['quantity'];
  }
  $form['worth_items'] = array(
    '#title' => t('Worth'),
    '#type' => 'fieldset',
    '#name' => 'worth_items',//this helps in the fieldset validation
    '#description' => t('What is the fixed value of this transaction?') .' '. t("This value will be overwritten if the passed entity has the 'worth' field"),
    '#tree' => TRUE,
    'worths' => array(
      //'#title' => t('Worths'),
      '#type' => 'worths',
      '#default_value' => $defaults,
      '#preset' => TRUE,//ensures that all currencies are rendered
    )
  );
  $form['round'] = array(
    '#title' => t('Rounding'),
    '#description' => t('Used if this transaction depends on another transaction'),
    '#type' => 'radios',
    '#options' => array('up' => t('Up'), 'down' => t('Down')),
    '#default_value' => isset($context['worth_items']['round']) ? $context['worth_items']['round'] : 'down',
    '#weight' => 1
  );

  if (variable_get('transaction_description_field')) {
    $form['description'] = array(
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
        '#type' => 'user_chooser_segment_perms',
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

//caches and returns and ALL the transaction operations
function _transaction_operations() {
  $operations = array();
  foreach (module_implements('transaction_operations') as $module) {
    $ops = array();
    $function = $module .'_transaction_operations';
    $ops = $function();
    foreach ($ops as $op => &$info) {
      $info += array(
        'access form' => 'operations_config_default_access',
        'op' => $op,
        'module' => $module
      );
      mcapi_operation_include($info);
      $props = array('submit callback', 'access callback', 'form callback');
      foreach ($props as $propname) {
        if (array_key_exists($propname, $info) && !is_bool($info[$propname]) && !function_exists($info[$propname])) {
          throw new Exception("Missing function or failed include in operation $op: '$propname' ".print_r($info, 1));
        }
      }
    }
    $operations += $ops;
  }
  uasort($operations, 'drupal_sort_weight');
  //now order the ops by weight
  drupal_alter('transaction_operations', $operations);
  cache()->set('transaction_operations', $operations);
  return $operations;
}


/*
 * implements hook_transaction_operations().
 * returns an array of operation infos keyed by the operation key
 * see transaction.api.php for more info
 *
 * Each op will have a MENU_LOCAL_ACTION at transaction/%serial/$op
 * note that filepath mcapi.inc is not needed because that file is included automatically
 */
function mcapi_transaction_operations() {
  $operations['mcapi_view'] = array(
    'title' => 'View',
    'access callback' => 'mcapi_view_access',
    'weight' => 0
  );
  t('View');
  $operations['mcapi_register'] = array(
    'title' => 'Register (internal use only)',
    'description' => 'A new transaction was registered',
    'weight' => 1
  );
  t('Register (internal use only)');
  t('A new transaction was registered');
  $operations['mcapi_update'] = array(
    'title' => 'Update (internal use only)',
    'description' => 'A transaction was changed',
    'weight' => 2
  );
  t('Update (internal use only)');
  t('A transaction was changed');

  $operations['mcapi_undo'] = array(
    'title' => 'Undo',
    'description' => 'Undo a finished transaction, and its dependents',
    'sure' => variable_get('mcapi_controller_delete_mode', MCAPI_CURRENCY_UNDO_REVERSE) == MCAPI_CURRENCY_UNDO_DELETE ?
      'Are you sure you want to undo? This transaction will disappear without a trace (apart from site backups)' :
      'Are you sure you want to undo? Only the site administrator will be able to restore this transaction.',
    'access callback' => 'transaction_op_undo_access',
    'submit callback' => 'transaction_op_undo',
    'form callback' => 'transaction_op_undo_form',
    'redirect' => 'user',
    'weight' => 10,
  );
  return $operations;
  t('Undo');
  t('Undo a finished transaction, and its dependents');
  t('Are you sure you want to undo? Only the site administrator will be able to restore this transaction.');
  t('Are you sure you want to undo? This transaction will disappear without a trace (apart from site backups)');
}

/*
 * user cancel method callback
 * see mcapi_form_user_cancel_confirm_form_alter
 */
function mcapi_user_cancel($edit, $account, $method) {
  if ($method == 'user_cancel_mcapi') {
    foreach(node_load_multiple(NULL, array('uid' => $account->uid)) as $node) {
      node_delete($node);
    }
  }
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
