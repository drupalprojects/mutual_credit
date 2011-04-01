<?php
// $Id: mcapi.inc,v 1.1.2.7 2010/12/22 19:30:29 matslats Exp $

//this $currencies is bit like a global, since we need it all over the place
//it is initialised at the moment that this file is included.
//this includes the controller file
mcapi_controller();

//$currency->divisions = currency_divisions_array($currency->divisions_conf);



/*
 * this is called by the hook mcapi_entity_info
 */
function mcapi_entity_info_inc() {
  $entities['transaction'] = array(
    'label' => t('Transaction'),
    'controller class' => 'TransactionEntityController', //inherits from default: DrupalEntityControllerInterface
    'base table' => 'mcapi_transactions',
    'load_hook' => 'transaction_load',
    'uri callback' => 'transaction_uri', //what is this for???
    'label callback' => 'transaction_label',
    'fieldable' => TRUE,
    'translation' => array(
      'locale' => FALSE,
    ),
    'entity keys' => array(
      'id' => 'xid',
    ),
    'bundles' => array(
      'transaction' => array(
        'label' => t('Transaction'),
        'admin' => array(
          'path' => 'admin/accounting/transaction/edit',
          'access arguments' => array('configure all currencies'),
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
  $entities['currency'] = array(
    'label' => t('Currency'),
    'controller class' => 'CurrencyEntityController', //inherits from default: DrupalEntityControllerInterface
    'base table' => 'mcapi_currencies',
    'load_hook' => 'currency_load',
    'uri callback' => 'currency_uri',
    'label callback' => 'currency_label',
    'fieldable' => TRUE,
    'translation' => array(
      'locale' => TRUE,
    ),
    'entity keys' => array(
      'id' => 'cid',
    ),
    'bundle keys' => array(
      'bundle' => 'type',
    ),
    'bundles' => array(
      'currency' => array(
        'label' => t('Currency'),
        'admin' => array(
          'path' => 'admin/accounting/currency/edit',
          'access arguments' => array('configure all currencies'),
        )
      )
    ),
    'view modes' => array(
      'default' => array(//will create this when the currency properties have settled down
        'label' => t('Currency page'),
        'custom settings' => FALSE,
      )
    )
  );
  return $entities;
}

function mcapi_menu_inc() {
  $items['admin/accounting'] = array(
    'title' => 'Accounting',
    'description' => 'Manage currencies and transactions',
    'page callback' => 'mcapi_options',
    'access arguments' => array('configure all currencies'),
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
  $items['admin/accounting/acknowledgements'] = array(
    'title' => 'Acknowledgements',
    'description' => "Software doesn't just grow on trees",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_acknowledgements'),
    'access arguments' => array('configure all currencies'),
    'file' => 'mcapi.inc',
  );
  $items['admin/accounting/entity_controller'] = array(
    'title' => 'Entity controller',
    'description' => "Choose which lump of code reads and writes your transaction data.",
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_choose_controller_form'),
    'access arguments' => array('configure all currencies'),
    'file' => 'mcapi.inc',
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
    'title' => 'Edit',
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
    'context' => MENU_CONTEXT_INLINE,
    'file' => 'mcapi.inc',
  );
  $items['currency/add'] = array(
    'title' => 'Declare currency',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('currency_form'),
    'access arguments' => array('declare currency'),
    'type' => MENU_CALLBACK,
    'file' => 'mcapi.inc',
  );
  $items['currency/%currency/edit'] = array(
    'title' => 'Modify currency',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('currency_form', 1),
    'access callback' => array('currency_access'),
    'access arguments' => array('edit', 1),
    'type' => MENU_CALLBACK,
    'file' => 'mcapi.inc',
  );
  //this is supposed for development purposes only

  $items['admin/accounting/refresh/%currency'] = array(
    'title' => 'Modify currency',
    'page callback' => 'mcapi_balances_refresh',
    'page arguments' => array(3),
    'access arguments' => array('configure all currencies'),
    'type' => MENU_CALLBACK,
    'file' => 'mcapi.inc',
  );
  return $items;
}

/*
 * page showing currencies and transaction_types
 */
function mcapi_options() {
  module_load_include('install', 'mcapi');
  module_load_include('admin.inc', 'system');
  $page  = '<h3>'. t('Currencies') .'</h3>'. show_currencies();
  $page .= system_admin_menu_block_page();

  return $page;
}

function mcapi_balances_refresh($currency) {
  $uids = db_select('mcapi_cache', 'c')->fields('c',array('uid'))->condition('cid', $currency->cid)->execute()->fetchCol();
  echo function_exists('balacnes_recalc');
  balances_recalc($currency->cid, $uids);
  drupal_goto('admin/accounting');
}

/*
 *
 */
function mcapi_choose_controller_form() {
  $controllers = file_scan_directory(drupal_get_path('module', 'mcapi'), '/.controller/');
  foreach ($controllers as $path => $file){
    $options[$file->name] = $file->filename;
  }

  $form['mcapi_controller'] = array(
    '#title' => 'Entity controller file',
    '#description' => t('Choose which .controller file, in modules/mutual_credit directory to use'),
    '#type' => 'radios',
    '#options' => $options,
    '#default_value' => variable_get('mcapi_controller', 'default')
  );

  return system_settings_form($form);
}


/*
 * get a list of uids of users with permission to transaction
 */
function get_trader_uids() {
  if ($roles = user_roles(TRUE, 'transaction')) {
    $query = db_select('users', 'u')
      //NEED TO SELECT DISTINCT
      //->join('users_roles', 'ur')
      ->fields('u', array('uid'))
      ->condition('u.status', 1)->condition('u.uid', 1, '>');
    if (!$roles[DRUPAL_AUTHENTICATED_RID]) {
      $query->condition('ur.rid', array_keys($roles));
    }
    return $query->execute()->fetchAll();
  }
  else {
    drupal_set_message(
      t(
        "No roles have permission to '@trade'. Go to !link",
        array(
          '@trade' => t('transaction'),
          '!link' => l('admin/user/permissions', 'admin/user/permissions')
        ), array('langcode' => 'warning'))
    );
  }
}

//this could almost have been done with a view, but as this is the API module, we don't want to create that dependency
//this will be done with the entitiy, and with a services hook
function show_currencies() {
  $currencies = &drupal_static('currencies');
  $entity = entity_get_info('transaction');
  $header = array(t('Format'), t('Name'), t('Transactions'), t(''));
  $output = '';
  foreach ($currencies as $currency) {
    $row['format'] = theme('money', array('quantity' => -99, 'cid' => $currency->cid));
    $row['title'] = l($currency->name, 'currency/' . $currency->cid);
    $row['usage'] = db_select('mcapi_transactions')->condition('cid', $currency->cid)->countQuery()->execute()->fetchField();
    $actions = array();
    if (currency_access('edit', $currency)) {
      $actions[] = l('edit', 'currency/' . $currency->cid . '/edit');
    }
    if ($entity['controller class'] == 'TransactionEntityController') {
      $actions[] = l(t('Refresh balances'), 'admin/accounting/refresh/'.$currency->cid);
    }
    $row['actions'] = implode(', ', $actions);
    $rows[] = $row;
  }
  if (user_access('declare currency')) {
    $rows[]= array(NULL, NULL, NULL, l(t('Declare new currency'), 'currency/add'));
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows));

  return $output;
}

function currency_form($form, $form_state, $currency = NULL) {
  if (isset($currency->name)) $title = t("Edit currency '@name'", array('@name' => $currency->name));
  else $title = t('Declare currency');
  drupal_set_title($title);

  $form = array(
    'cid' => array(
      '#title' => t('Currency ID'),
      '#type' => 'textfield',
      '#size' => isset($currency->cid) ? strlen($currency->cid) : 1,
      '#default_value' => isset($currency->cid) ? $currency->cid : '',
      '#weight' => -21
    ),
    'name' => array(
      '#title' => t('Name of currency'),
      '#description' => t('Use the plural'),
      '#type' => 'textfield',
      '#default_value' => isset($currency->name) ? $currency->name : 0,
      '#element_validate' => array('mcapi_currency_validate_name'),
      '#weight' => -18
    ),
    'format' => array(
      '#title' => t('Display format'),
      '#description' => t('Write an expression to control the display of the currency using the following tokens:') .' @icon, @quant, @subdivision ' .
         t('For example to display -$45:23 AUS, enter @minus@icon@integer:@subdivision AUS'),
      '#type' => 'textfield',
      '#default_value' => isset($currency->format) ? $currency->format : '',
      '#element_validate' => array('mcapi_currency_validate_format'),
      '#required' => TRUE,
      '#weight' => -10
    ),
    'divisions_conf' => array(
      '#title' => t('Allowed divisions'),
      '#description' => t('Leave blank to use integers. "00" to use hundredths.') . t('To use a dropdown, such as for quarters of an hour, write a "hundredths value| visible text" on each line. E.g. ') . '<br />' .
        '<pre>0| exactly<br />50| half an hour</pre>',
      '#type' => 'textarea',
      '#default_value' => isset($currency->divisions_conf) ? $currency->divisions_conf : '',
      '#element_validate' => array('mcapi_currency_validate_divisions'),
      '#weight' => -5
    ),
  );
  $form['username'] = array(
    '#type' => 'textfield',
    '#title' => t('Declared by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => !empty($currency->uid) ? user_load($currency->uid)->name : $GLOBALS['user']->name,
    '#element_validate' => array('mcapi_validate_user'),
    '#required' => TRUE,
    '#weight' => 10,
  );

  field_attach_form('currency', $currency, $form, $form_state);

  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 15,
  );
  $form['access'] = array(
    '#title' => t('Availability'),
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
    '#weight' => 3,
    'access_callback' => array(
      '#title' => t('Criterion to use this currency'),
      '#description' => t('How is access to this currency determined?'),
      '#type' => 'radios',
      '#options' => array(
        'user_access' => t('Everyone can use'),
        'currencies_access_viral' => t('Spreads virally. Only users who have already traded in this currency can initiate transactions with it'),
      ),
      '#default_value' => isset($currency->access_callback) ? $currency->access_callback : '',// empty means user_access('transact')
    ),
  );
  $form['privacy'] = array(
    '#title' => t('Privacy'),
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
    '#weight' => 4,
    'privacy' => array(
      '#title' => t('Visibility of transactions in this currency'),
      '#type' => 'radios',
      '#options' => array(
        0 => t("Only participants"),
        1 => t('Only participants, accountant and user 1'),
        2 => t("All users of this currency"),
        3 => t("All authenticated users"),
        4 => t('Public'),
      ),
      '#default_value' => isset($currency->privacy) ? $currency->privacy : 1,
    )
  );
  $form['malleability'] = array(
    '#title' => t('Malleability'),
    '#description' => t('Only applies if editing is permitted by accounting standards.').' (AJAX needed!)',
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
    '#weight' => 5,
    //could use some D7 formAPI AJAX here to disable this field if update_mode is 0
    'user_editable_mode' => array(
      '#title' => t('User editable'),
      '#type' => 'radios',
      '#options' => array(
        0 => t("Users cannot edit"),
        1 => t('Creator can edit/delete pending transactions'),
        2 => t('Both can edit/delete pending transactions'),
        3 => t('Both can edit/delete finished transactions'),
      ),
      '#default_value' => isset($currency->user_editable_mode) ? $currency->user_editable_mode : 0,
      '#weight' => 4
    )
  );
  $form['#entity_builders'][] = 'currency_entity_builder';

  $form['actions'] = array(
    '#weight' => 20,
  );

  if (isset($currency->cid)) {
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
    );
  }
  else {
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Record'),
    );
  }
  return $form;
}

function mcapi_currency_validate_divisions(&$element, $form_state) {
  $divisions = $element['#value'];
  if ($divisions == '00' || $divisions == '') return;
  $lines = explode("\n", $divisions);
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

function mcapi_validate_user(&$element, $form_state) {
  //copied from node_validate
  if (!empty($element['#value']) && !($account = user_load_by_name($element['#value']))) {
    form_error($element, t('The username %name does not exist.', array('%name' => $element['#value'])));
  }
}
function mcapi_currency_validate_update_mode(&$element, $form_state) {
  if ($element['#value'] == 1 && $form_state['values']['delete_mode'] == 0) {
    form_error($element, t('Accounting standards') .' '. t('Update mode is incompatible with delete mode'));
  }
}

/*
 * Called from the formAPI
 * Checks for duplicate names
 */
function mcapi_currency_validate_name(&$element, $form_state) {
  if (!isset($form_state['values']['cid']) || $form_state['values']['cid'] == 0) return;
  $cid = $form_state['values']['cid'];
  //check that the title is unique amongst currencies
  $count = db_select('mcapi_currencies', 'c')
  ->condition('name', $element['#value'])
  ->condition('cid', $cid, '<>')
  ->countQuery()->execute()->fetchField();
  if ($count > 0) {
    form_set_error($element, t('Another currency called !name already exists.', array(
      '!name' => l($element['#value'], 'currency/' . $cid)
    )));
  }
}

function mcapi_currency_validate_format(&$element, $form_state) {
  if (!strpos($element['#value'], '@integer')) {
    form_error($element, t("Currency format must contain token '@integer'"));
  }
}

function currency_form_submit($form, &$form_state) {
  $currency = new stdClass;
  entity_form_submit_build_entity('currency', $currency, $form, $form_state);
  entity_get_controller('currency')->save($currency);
  if (user_access('configure all currencies')) {
    drupal_goto('admin/accounting');
  }
  else {
    drupal_goto('user');
  }
}
/*
 * callback which builds the entity from the form submission
 */
function currency_entity_builder($entity_type, $entity, &$form, &$form_state) {
  $entity->data['access_callback'] = $entity->access_callback;
  $entity->data['privacy'] = $entity->privacy;
  $entity->data['user_editable_mode'] = $entity->user_editable_mode;
}



/**
 * since all transaction forms are based on the same generic 'transaction_form'
 * we use hook_form_FORM_ID_alter to modify different forms.
 * This module provides only one form, intended for admin to use.
 */
function mcapi_form_transaction_admin_form_alter(&$form, $form_state) {
  $available_currencies = currency_select(currency_choose('initiate', $GLOBALS['user']->uid));
  if (count($available_currencies) > 1) {
    $available_currencies = array(t('Choose currency...')) + $available_currencies;
  }
  $form['cid'] = array(
    '#title' => t('Currency'),
    '#description' => t('The ID of the currency'),
    '#type' => 'select',
    '#options' => $available_currencies,
    '#weight' => 0,
    '#required' => TRUE,
    '#element_validate' => array('mcapi_validate_cid'),
    '#default_value' => $form['cid']['#default_value']
  );
}

/**
 * All transaction forms should use this function as a basis using hook_forms
 * the cid widget if needed is provided by hook_form_FORM_ID_alter
 */
function transaction_form($form, $form_state, $transaction = NULL) {
  $xid = isset($transaction->xid) ? $transaction->xid : NULL;
  //the currency chooser if needed is done by form_alter
  $form['cid'] = array(
    '#type' => 'hidden',
    '#default_value' => isset($transaction->cid) ? $transaction->cid : '',
  );
  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Reason for transaction'),
    '#default_value' => isset($transaction->description) ? $transaction->description : '',
    '#element_validate' => array('mcapi_validate_title'),
    '#weight' => 3,
  );

  $form['payer_uid'] = array(
    '#type' => 'textfield',
    '#title' => t('Account to be debited'),
    '#default_value' => isset($transaction->payer_uid) ? $transaction->payer_uid : '',
    '#element_validate' => array('mcapi_validate_trader'),
    '#weight' => 6,
    '#size' => 7
  );
  $form['payee_uid'] = array(
    '#type' => 'textfield',
    '#title' => t('Account to be credited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => isset($transaction->payee_uid) ? $transaction->payee_uid : '',
    '#element_validate' => array('mcapi_validate_trader'),
    '#weight' => 9,
    '#size' => 7
  );
  $form['quantity'] = array(
    '#type' => 'textfield',
    '#title' => t('Quantity'),
    '#element_validate' => array('mcapi_validate_quantity'),
    '#default_value' => isset($transaction->quantity) ? $transaction->quantity : '',
    '#weight' => 12,
    '#size' => 7,
  );
  $types = module_invoke_all('transaction_type_info');
  $form['type'] = array(
    '#title' => t('Transaction type'),
    '#options' => mcapi_title_selector($types, 'title', t('Please choose...')),
    '#type' => 'select',
    '#default_value' => isset($transaction->type) ? intval($transaction->type) : 'unspecified',
    '#element_validate' => array('mcapi_validate_ttype'),
    '#required' => TRUE,
    '#weight' => 15
  );
  $form['state'] = array(
    '#type' => 'radios',
    '#title' => t('State'),
    '#description' => t('Finished transactions cannot have empty values.'),
    '#default_value' => isset($transaction->state) ? intval($transaction->state) : '0', //defaults to 0, or TRANSACTION_STATE_FINISHED
    '#options' => array(
      TRANSACTION_STATE_FINISHED => t('Finished'),
      TRANSACTION_STATE_PENDING => t('Pending'),
    ),
    '#element_validate' => array('mcapi_validate_state'),
    '#weight' => 18
  );
  field_attach_form('transaction', $transaction, $form, $form_state);
  $form['actions']['#weight'] = 25;
  if ($xid) {
    $form['xid'] = array(
      '#title' => t('Transaction ID'),
      '#type' => 'textfield',
      '#default_value' => $xid,
      '#disabled' => TRUE,
      '#weight' => -1,
      '#attributes' => array('size' => $xid ? strlen($xid) : 1 ),
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
    );
  }
  else {
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Record'),
    );
  }
  switch (variable_get('mcapi_delete_mode', '0')) {
    case '1':
      $form['state']['#options'][TRANSACTION_STATE_ERASED] = t('Erased');
        break;
    case '2':
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
      break;
  }
  $form['#validate'] = array('mcapi_prevalidate_transaction', 'transaction_form_validate');
  $form['#redirect'] = 'user';
  return $form;
}

/*
 * 
 * Prepares a transaction object in the $form_state
 */
function mcapi_prevalidate_transaction($form, &$form_state) {
  global $user;
  $form_state['#transaction'] = (object)array(
    'payer_uid' => $form_state['values']['payer_uid'],
    'payee_uid' => $form_state['values']['payee_uid'],
    'quantity' => $form_state['values']['quantity'],
    'cid' => $form_state['values']['cid'],
    'description' =>  isset($form_state['values']['description']) ?  $form_state['values']['description'] : t('no description given'), //shouldn't really be omitted
    'state' => isset($form_state['values']['state']) ?  $form_state['values']['state'] : TRANSACTION_STATE_FINISHED, //shouldn't really be omitted
    'type' => isset($form_state['values']['type']) ?  $form_state['values']['type'] : 'unspecified', //shouldn't really be omitted
    'uid' => isset($form_state['values']['uid']) ?  $form_state['values']['uid'] : $GLOBALS['user']->uid,
    'created' => REQUEST_TIME,
    'data' => array(),
  );
  if (isset($form_state['values']['xid'])) {
    $form_state['#transaction']->xid = $form_state['values']['xid'];
  }
  return $form;
}


/*
 * standard validation function for ALL transaction forms
 * fields have already been validated individually
 * and $form_state['#transaction'] has been created by mcapi_prevalidate_transaction
 * Anything that validates in this function should work in the database
 * Even if the form wasn't properly designed in all respects.
 * This validation process allows NULL fields.
 * which also means it can validate partially completed forms
 */
function transaction_form_validate($form, &$form_state) {
  field_attach_form_validate('transaction', (object) $form_state['values'], $form, $form_state);
  try {
    mcapi_transaction_validate(&$form_state['#transaction'], $form);
  }
  catch (Exception $e) {
   drupal_set_message($e->getMessage(), 'warning');
  }
}
/*
 * Universal transaction validation
 */
function mcapi_transaction_validate($transaction, &$form = NULL){
  //no usecase is currently envisaged where someone might pay themselves
  if ($transaction->payer_uid > 0) {
    if ($transaction->payer_uid == $transaction->payee_uid) {
      form_set_error('', t('A transaction must involve two different users'));
    }
  }

  //a transaction can only be complete if all these fields are filled in.
  if ($transaction->state == TRANSACTION_STATE_FINISHED) {
    $required = array('payer_uid', 'payee_uid', 'quantity', 'cid', 'type');
    foreach ($required as $fieldname) {
      if (!$transaction->$fieldname) {
        if (isset($form[$fieldname])) {
          form_set_error($fieldname, t("@fieldname required for completed transaction", array('@fieldname' => $form[$fieldname]['#title'])));
        }
        else
          form_set_error(t("@fieldname required for completed transaction", array('@fieldname' => $fieldname)));
      }
    }
  }

  //check that each user is permitted to use that currency
  $currency = currency_load($transaction->cid);
  foreach (array($transaction->payer_uid, $transaction->payee_uid) as $uid) {
    if (!currency_access('use', $currency, $uid)) {
      form_set_error('cid', t("!name is not permitted to use @currency", array(
        '!name' => theme('username', array('account' => user_load($transaction->payer_uid))),
        '@currency' => $currency->name
      )));
    }
  }
  
  if ($transaction->payer_uid && $transaction->payee_uid) {
    //this concerns rules and database integrity, so we hand it off to the db controller
    $transactionController = entity_get_controller('transaction');
      //we're checking the balances, and trial-entering the transaction in the db
    $transactionController->validate($transaction);
    //both should produce the same results, only one is necessary
  }
}

/*
 * Submit callback for default transaction form
 */
function transaction_admin_form_submit($form, &$form_state) {
  $transaction = new stdClass;
  entity_form_submit_build_entity('transaction', $transaction, $form, $form_state);
  entity_get_controller('transaction')->save($form_state['#transaction'], TRUE);
  if (isset($form_state['#transaction']->xid)) {
    drupal_set_message(t('Transaction recorded.'));
  }
  if (!isset($form_state['redirect'])) {
    //$form_state['redirect'] = 'transaction/'.$form_state['#transaction']->xid;
  }
}


/*
 * Couldn't be bothered to provide a theme callack for such a rarely used form.
 */
function mcapi_transaction_erase_confirm_form($form, $transaction){
  if ($form['#parameters'][2]->type == 'transaction') {
    drupal_set_title(t('Are you sure you want to erase this transaction?'));
    $form['#prefix'] = '<blockquote>' . $transaction->title . '</blockquote>';
    if ($transaction->state == TRANSACTION_STATE_PENDING) {
      $form['description']['#value'] = t('The transation had not been confirmed');
    }
    elseif ($transaction->state == TRANSACTION_STATE_FINISHED) {
      $form['description']['#value'] = t('This transaction was confirmed, so the balances of both users will be affected');
    }
  }
}

/**
 * delete a single transaction
 */
function transaction_erase($xid) {
  $transaction = entity_get_controller('transaction')->load($xid);
  return entity_get_controller('transaction')->erase($transaction);
}


function odd_cents($float) {
    $integer = intval($float);
    //format using the given currency subdivision
    $fraction = $float - $integer;
    return ceil($fraction * 100); //corrects the floating point error
}


function mcapi_validate_title($element, $form_state) {
  //don't know the max length of 'small' text field. Will be just truncated
  $form_state['values']['title'] = check_plain($element['#value']);
}

function mcapi_validate_cid(&$element, $form_state) {
  if (isset($element['#value']) && $element['#value']) {
    $currency = currency_load($element['#value']);
    if (!is_object($currency)) { //you would also check here if the user had permission to use this currency
      form_error($element, t('Invalid currency specified: @num', array('@num' => $element['#value'])));
    }
  }
}


/*
 * Validate the quantity field on the transaction form
 */
function mcapi_validate_quantity(&$element, &$form_state) {
  $value = $element['#value'] + 0;//convert it from string to a number
  //null values allowed
  if (!$value)return;

  if ($element['#value'] < 0 ) {
    form_error($element, t("Negative values not allowed for '@fieldname'", array('@fieldname' => $element['#title'])));
  }
  $fraction = odd_cents($value);
  if ($fraction && isset($form_state['values']['cid']) && $currency = all_currencies($form_state['values']['cid'])) {
    if (is_null($currency->divisions) && !is_integer($value)) {
      form_error($element, t("Integer values only for '@currency'", array('@currency' => $currency->title)));
    }
    elseif($currency->divisions == '00') {
      return;
    }
    elseif (isset($currency->divisions)) {
      if ($fraction && !isset($currency->divisions[$fraction])) {
        form_error($element, t("Invalid fraction for '@fieldname'", array('@fieldname' => $element['#title'])));
      }
    }
  }
}

//the api module will validate any number or username or email as a user with permission to trade
//this function permits blank values
function mcapi_validate_trader(&$element, &$form_state) {
  if ($val = $element['#value']) {
    _mcapi_check_trader($val, $element);
  }
}

function _mcapi_check_trader($value, &$element = NULL) {
  if (is_numeric($value)) {
    $field = 'uid';
  }
  elseif (valid_email_address($value)) {
    $field = 'mail';
  }
  elseif (is_string($value) && strlen($value)) {
    $field = 'name';
  }
  $criterion = array($field => $value);
  /*
   * Check this
   */
  $account = array_shift(user_load_multiple(array(), $criterion));
  if (isset($account->uid)) {
    if (user_access('transact', $account)   && $account->status) {
      return $account->uid;
    }
    else {
      if ($element) {
        form_error($element, t("@user is not permitted to trade", array("@user" => $account->name)));
      }
    }
  }
  else {
    if ($element) {
      form_error($element, t("@data is not a known user", array("@data" => $value)));
    }
  }
}
function mcapi_validate_state($element, $form_state) {
  $states = array(TRANSACTION_STATE_FINISHED, TRANSACTION_STATE_PENDING, TRANSACTION_STATE_ERASED);
  if (!in_array($element['#value'], $states)) {
    form_error($element, t('Invalid transaction state.') .' '. t('Suspected hacking attempt!'));
  }
}
function mcapi_validate_ttype($element, $form_state) {
  $types = module_invoke_all('transaction_type_info');
  if (!is_array($types[$element['#value']])) {
    form_error($element, t('Invalid transaction type.') .' '. t('Suspected hacking attempt!'));
  }
}
