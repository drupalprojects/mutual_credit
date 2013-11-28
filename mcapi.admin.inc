<?php

use Drupal\Core\Template\Attribute;

/*
 * implements hook_menu_alter
 */
function mcapi_menu_alter(&$items) {
  $items['admin/accounting/fields']['title'] = 'Transactions FieldAPI';
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
    if (!$currency->status) continue;
    //convert the divisions_allowed into an array
    $divisions = array();
    if ($currency->widget == CURRENCY_WIDGET_SELECT) {
      foreach(explode("\n", $currency->settings['divisions']) as $line) {
        list($cent, $display) = explode('|', $line);
        $divisions[$cent] = trim($display);
      }
    }
    //this property exists only in memory, it is the array version of the custom options text field
    $currency->divisions_allowed = $divisions;
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

  $form['description'] = array(
    '#title' => t('Transaction description text'),
    '#type' => 'textfield',
    '#default_value' => @$context['worth_items']['description'],
  );

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
  $form['presets']['description'] = array(
    '#title' => t('Transaction description text'),
    '#type' => 'textfield',
    '#default_value' => @$context['presets']['description'],
  );

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


function mcapi_workflow_help() {
  drupal_add_css('table.help{float:left;margin-right:1em; width:50%;} ul.admin-list{clear:both;}', array('type' => 'inline'));
  $renderable = array(
    'states' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction states, from !hook', array('!hook' => "\n<br />hook_mcapi_info_states()")),
      '#header' => array(t('Name'), t('Description')),
      '#attributes' => array('class' => array('help'))
    ),
    'types' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction types, from !hook.', array('!hook' => "\n<br />hook_mcapi_info_types()")),
      '#header' => array(t('machine name')),
      '#attributes' => array('class' => array('help'))
    ),
  );
  foreach (mcapi_get_states('#full') as $name => $info) {
    $renderable['states']['#rows'][$name]['name'] = $info['name'];
    $renderable['states']['#rows'][$name]['dscription'] = $info['description'];
  }
  foreach (mcapi_get_types() as $type) {
    $renderable['types']['#rows'][$type]['name'] = $type;
  }
  debug($renderable);
  return $renderable;
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
        'module' => $module,
        'mail' => FALSE
      );
      mcapi_operation_include($info);
      $required = array('submit callback', 'access callback', 'form callback');
      foreach ($required as $propname) {
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
  $operations['view'] = array(
    'title' => 'View',
    'access callback' => 'mcapi_view_access',
    'weight' => 0
  );
  t('View');
  $operations['register'] = array(
    'title' => 'Register (internal use only)',
    'description' => 'A new transaction was registered',
  );
  t('Register (internal use only)');
  t('A new transaction was registered');
  $operations['update'] = array(
    'title' => 'Update (internal use only)',
    'description' => 'A transaction was changed',
  );
  t('Update (internal use only)');
  t('A transaction was changed');

  $operations['undo'] = array(
    'title' => 'Undo',
    'description' => 'Undo a finished transaction, and its dependents',
    'sure' => \Drupal::config('mcapi.misc')->get('delete_mode') == MCAPI_CURRENCY_UNDO_DELETE ?
      'Are you sure you want to undo? This transaction will disappear without a trace (apart from site backups)' :
      'Are you sure you want to undo? Only the site administrator will be able to restore this transaction.',
    'access callback' => 'transaction_op_undo_access',
    'submit callback' => 'transaction_op_undo',
//    'form callback' => 'transaction_op_undo_form',
    'mail' => TRUE,
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
