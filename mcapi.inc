<?php
// $Id: mcapi.inc,v 1.1.2.7 2010/12/22 19:30:29 matslats Exp $

//this $currencies is bit like a global, since we need it all over the place
//it is initialised at the moment that this file is included.
//this includes the controller file


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
    'page callback' => 'mcapi_accounting',
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
    'page arguments' => array('mcapi_controller_options_form'),
    'access arguments' => array('configure all currencies'),
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
    'access callback' => 'user_access',
    'access arguments' => array('manage all transactions'),
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
  /*
  $items['user/%user/transactions_list'] = array(
    'title' => 'Transactions',
    'page callback' => 'mcapi_statement',
    'page arguments' => array(1),
    'access callback' => 'user_edit_access',
    'access arguments' => array(1),
    'file' => 'mcapi.inc',
    'type' => MENU_LOCAL_TASK, 
  );
*/
  //this is supposed for development purposes only, and may not apply to some controllers
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
function mcapi_accounting() {
  module_load_include('install', 'mcapi');
  module_load_include('admin.inc', 'system');
  check_default_currency();
  $page  = '<h3>'. t('Currencies') .'</h3>'. show_currencies();
  $page .= system_admin_menu_block_page();

  return $page;
}

function mcapi_balances_refresh($currency) {
  $uids = db_select('mcapi_cache', 'c')->fields('c',array('uid'))->condition('cid', $currency->cid)->execute()->fetchCol();
  echo function_exists('balances_recalc');
  balances_recalc($currency->cid, $uids);
  drupal_goto('admin/accounting');
}


//this could almost have been done with a view, but as this is the API module, we don't want to create that dependency
//this will be done with the entitiy, and with a services hook
function show_currencies() {
  $currencies = currencies_load();
  $entity = entity_get_info('transaction');
  $header = array(t('Format'), t('Name'), t('Access callback'), t('Count'), t('Declared by'), NULL);
  $output = '';
  foreach ($currencies as $currency) {
    $row['format'] = mcapi_format_money(-99, $currency->cid);
    $row['title'] = l($currency->name, 'currency/' . $currency->cid);
    $row['availability'] = $currency->extra['access_callback'];
    $row['count'] = transaction_controller('count', array('cid' => $currency->cid));
    $row['owner'] = format_username(user_load($currency->uid));
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
    $rows[]= array(NULL, NULL, NULL, NULL, NULL, l(t('Declare new currency'), 'currency/add'));
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows));

  return $output;
}

/*
 * build the currency edit form
 * I'm not sure yet, how arguments are supposed to be passed in a situation like this
 * and wouldn't it be nice if we could do 'new currency' and not have to check whether each object property isset
 */
function currency_form($form, $form_state, $currency = NULL) {
  if (!$currency) {
    $currency = new currency;
    $currency->extra['update_mode'] = 0;
    $currency->extra['delete_mode'] = 0;
    $currency->extra['access_callback'] = 'user_access';
    $currency->extra['privacy'] = 1;
  }
  else {
    drupal_set_title( !empty($currency->name) ? $title = t("Edit currency '@name'", array('@name' => $currency->name)) : t('Declare currency') );
  }
  $form['#currency'] = $currency;
  $form += array(
    'cid' => array(
      '#type' => 'hidden',
      '#value' => isset($currency->cid) ? $currency->cid : '',
    ),
    'name' => array(
      '#title' => t('Name of currency'),
      '#description' => t('Use the plural'),
      '#type' => 'textfield',
      '#default_value' => empty($form_state['values']['name']) ? strval($currency->name) : $form_state['values']['name'],
      '#element_validate' => array('mcapi_currency_validate_name'),
      '#weight' => -18
    ),
    'format' => array(
      '#title' => t('Display format'),
      '#description' => t('Write an expression to control the display of the currency using the following tokens:') .' @minus, @icon, @integer, @subdivision ' .
         t('For example to display -$45:23 AUS, enter @minus@icon@integer:@subdivision AUS'),
      '#type' => 'textfield',
      '#default_value' => empty($form_state['values']['format']) ? strval($currency->format) : $form_state['values']['format'],
      '#element_validate' => array('mcapi_currency_validate_format'),
      '#required' => TRUE,
      '#weight' => -10
    ),
    'divisions_conf' => array(
      '#title' => t('Allowed divisions'),
      '#description' => t('Leave blank to use integers. "00" to use hundredths.') . t('To use a dropdown, such as for quarters of an hour, write a "hundredths value| visible text" on each line. E.g. ') . '<br />' .
        '<pre>0|/.<br />25|1/4<br />50|1/2<br />75|3/4</pre>',
      '#type' => 'textarea',
      '#default_value' => empty($form_state['values']['divisions_conf']) ? strval($currency->divisions_conf) : $form_state['values']['divisions_conf'],
      '#element_validate' => array('mcapi_currency_validate_divisions'),
      '#weight' => -5
    ),
  );
  $form['username'] = array(
    '#type' => 'textfield',
    '#title' => t('Declared by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => empty($form_state['values']['uid']) ? user_load($currency->uid)->name : user_load($form_state['values']['uid'])->name,
    '#element_validate' => array('mcapi_validate_user'),
    '#required' => TRUE,
    '#weight' => 10,
  );

  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 15,
  );
  $form['accounting'] = array(
    '#title' => t('Accounting standards'),
    '#description' => t('More or less formal accounting standards can be determined.') .' '.
      t('Not all combinations are meaningful!') .' '.
      t('The first fields take precedence'),
    '#type' => 'fieldset',
    '#weight' => 0,
    '#group' => 'additional_settings',
    'update_mode' => array(
      '#title' => t('Update mode'),
      '#type' => 'radios',
      '#options' => array(
        0 => t("Can't update"),
        1 => t("Delete and create a new transaction"),
        2 => t("Update same record"),
        //3 => t("Use entity versioning the same transaction (not available)"),
      ),
      '#default_value' => empty($form_state['values']['update_mode']) ? $currency->extra['update_mode'] : $form_state['values']['update_mode'],
      '#weight' => 1,
      '#element_validate' => array('mcapi_currency_validate_update_mode'),
      '#ajax' => array(
        'callback' => 'currency_form_ajax',
        'wrapper' => 'currency-form',
      ),
    ),
    'delete_mode' => array(
      '#title' => t('Delete mode'),
      '#type' => 'radios',
      '#options' => array(
        0 => t("Can't delete"),
        1 => t('Mark deleted'),
        2 => t('Remove all traces from the database (not from backups)'),
      ),
      '#default_value' => empty($form_state['values']['delete_mode']) ? $currency->extra['delete_mode'] : $form_state['values']['delete_mode'],
      '#weight' => 2,
      '#ajax' => array(
        'callback' => 'currency_form_ajax',
        'wrapper' => 'currency-form',
      ),
    )
  );
  //alter the options according to what is set
  if ($form['accounting']['delete_mode']['#default_value'] == 0) {
    unset($form['accounting']['update_mode']['#options'][1]);
    if ($form['accounting']['update_mode']['#default_value'] == 1);
    $form['accounting']['update_mode']['#default_value'] = 0;
  }
  //nasty design flaw around radio buttons which means ajax rebuilds them wrong
  if (arg(0) == 'system') {
    unset($form['accounting']['update_mode']['#title']);
  }
  $form['access'] = array(
    '#title' => t('Availability'),
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
    '#weight' => 5,
    'access_callback' => array(
      '#title' => t('Criterion to use this currency'),
      '#description' => t('How is access to this currency determined?'),
      '#type' => 'radios',
      '#options' => array(
        'user_access' => t('Everyone can use'),
        'currencies_access_viral' => t('Spreads virally. Only users who have already traded in this currency can initiate transactions with it'),
      ),
      '#default_value' =>  empty($form_state['values']['access_callback']) ? strval($currency->extra['access_callback']) : $form_state['values']['access_callback'],// empty means user_access('transact')
    ),
  );
  $form['privacy'] = array(
    '#title' => t('Privacy'),
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
    '#weight' => 3,
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
      '#default_value' => empty($form_state['values']['privacy']) ? intval($currency->extra['privacy']) : $form_state['values']['privacy'],
    )
  );

  field_attach_form('currency', $currency, $form, $form_state);
  $form['#entity_builders'][] = 'currency_entity_builder';

  $form['buttons'] = array(
    '#weight' => 20,
  );

  if (isset($currency->cid)) {
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
    );
  }
  else {
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Record'),
    );
  }
  return $form;
}

function currency_form_ajax(&$form, &$form_state) {
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


/*
 * Called from the formAPI
 * Checks for duplicate currency names
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
  $currency = new currency;
  entity_form_submit_build_entity('currency', $currency, $form, $form_state);
  currency_controller('save', $currency);
  //cache_clear_all('currencies');
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
  $entity->extra['delete_mode'] = $entity->delete_mode;
  $entity->extra['update_mode'] = $entity->update_mode;
  $entity->extra['access_callback'] = $entity->access_callback;
  $entity->extra['privacy'] = $entity->privacy;
  $entity->extra['user_editable_mode'] = empty($entity->extra['user_editable_mode']) ? 0 : $entity->extra['user_editable_mode'];
}

/**
 * implements hook_form_FORMID_alter
 * since all transaction forms are based on the same generic 'transaction_form'
 * we use hook_form_FORM_ID_alter to modify specific forms.
 * This function modifies the one form provided by this module, putting in a currency selector
 */
function mcapi_form_transaction_admin_form_alter(&$form, $form_state) {
  $available_currencies = currency_select(currency_choose('initiate', $GLOBALS['user']->uid));
  if (count($available_currencies) > 1) {
    drupal_set_title(t('Admin editing transaction #@xid', array('@xid' => $form['xid']['#default_value'])));
    $form['cid']['#type'] = 'select';
    $form['cid']['#options'] = array(t('Choose currency...')) + $available_currencies;
  }
}

/**
 * All transaction forms should use this function as a basis using hook_forms
 * the cid widget if needed is provided by hook_form_FORM_ID_alter
 */
function transaction_form($form, $form_state, $transaction) {
  //the currency chooser if needed is done by form_alter
  $form['xid'] = array(
    '#type'=> 'hidden',
    '#value' => intval($transaction->xid)
  );
  $form['cid'] = array(
    '#title' => t('Currency'),
    '#description' => t('The ID of the currency'),
    '#type' => 'textfield',
    '#default_value' => $transaction->cid,
    '#weight' => 0,
    '#element_validate' => array('mcapi_validate_cid'),
  );

  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Reason for transaction'),
    '#default_value' => $transaction->description,
    '#element_validate' => array('mcapi_validate_title'),
    '#weight' => 3,
    '#attributes' => array('style' => 'width:100%;')
  );

  $form['payer'] = array(
    '#type' => 'textfield',
    '#title' => t('Account to be debited'),
    '#default_value' => $transaction->payer,
    '#element_validate' => array('mcapi_validate_user'),
    '#weight' => 6,
  );
  $form['payee'] = array(
    '#type' => 'textfield',
    '#title' => t('Account to be credited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => $transaction->payee,
    '#element_validate' => array('mcapi_validate_user'),
    '#weight' => 9,
  );
  $form['quantity'] = array(
    '#type' => 'textfield',
    '#title' => t('Quantity'),
    '#element_validate' => array('mcapi_validate_quantity'),
    '#default_value' => $transaction->quantity,
    '#weight' => 12,
  );
  $form['type'] = array(
    '#title' => t('Transaction type'),
    '#options' => drupal_map_assoc(module_invoke_all('transaction_info', 'types')),
    '#type' => 'select',
    '#default_value' => $transaction->type,
    '#element_validate' => array('mcapi_validate_ttype'),
    '#required' => TRUE,
    '#weight' => 15
  );
  $form['state'] = array(
    '#type' => 'radios',
    '#title' => t('State'),
    '#description' => t('Finished transactions cannot have empty values.'),
    '#default_value' => $transaction->state,
    '#options' => module_invoke_all('transaction_info', 'states'),
    '#element_validate' => array('mcapi_validate_state'),
    '#weight' => 18
  );
  //TODO
  //I'm just fed up of multistep forms, this will have to wait
  //the following doesn't retrieve default values out of form_state when we come back from step 2
  field_attach_form('transaction', $transaction, $form, $form_state);
  $form['buttons']['#weight'] = 25;

  $xid = $transaction->xid;
  if ($xid) {
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Update'),
    );
  }
  else {
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Record'),
    );
  }
  //sort out the delete options
  if (!empty($transaction->cid)) {
    $currency = currency_load($transaction->cid);
    if (isset($currency->extra['delete_mode'])) {
      switch ($currency->extra['delete_mode']) {
        case '1': //we can update the currency row by simply changing the state
          $form['state']['#options'][TRANSACTION_STATE_ERASED] = t('Erased');
            break;
        case '2': //if hard deleting is allowed then provide a button
          if ($transaction->state == TRANSACTION_STATE_VALIDATING) continue;
          $form['buttons']['delete'] = array(
            '#type' => 'submit',
            '#value' => t('Delete'),
          );
          break;
      }
    }
  }
  $form['#entity_builders'][] = 'transaction_entity_builder';
  $form['#validate'] = array('transaction_form_validate');
  $form['#submit'] = array('transaction_all_submit');
  $form_state['redirect'] = 'user';
  return $form;
}

/*
 * standard validation function for ALL transaction forms
 * fields have already been validated individually
 * Anything that validates in this function should work in the database
 * Even if the form wasn't properly designed in all respects.
 * This validation process allows NULL fields.
 * which also means it can validate partially completed forms
 */
function transaction_form_validate($form, &$form_state) {ddebug_backtrace();
  if (empty($form_state['#transaction'])) {
    if (!empty($form_state['values']['xid'])) {
      $form_state['#transaction'] = transaction_load($form_state['values']['xid']);
    }
    else {
      $form_state['#transaction'] = new transaction;
    }
  }
  entity_form_submit_build_entity('transaction', $form_state['#transaction'], $form, $form_state);
  field_attach_form_validate('transaction', $form_state['#transaction'], $form, $form_state);
  try {
    _mcapi_transaction_validate($form_state['#transaction'], $form);
  }
  catch (Exception $e) {
   drupal_set_message($e->getMessage(), 'warning');
  }
}
/*
 * Universal transaction validation
 * called from transaction_form_validate
 */
function _mcapi_transaction_validate(&$transaction, &$form = NULL){
  //no usecase is currently envisaged where someone might pay themselves
  if ($transaction->payer > 0) {
    if ($transaction->payer == $transaction->payee) {
      form_set_error('', t('A transaction must involve two different users'));
    }
  }

  //a transaction can only be complete if all these fields are filled in.
  if ($transaction->state == TRANSACTION_STATE_FINISHED) {
    $required = array('payer', 'payee', 'quantity', 'cid', 'type');
    foreach ($required as $fieldname) {
      if (!$transaction->$fieldname) {
        if (isset($form[$fieldname])) {
          form_set_error($fieldname, t("@fieldname required for completed transaction", array('@fieldname' => $form[$fieldname]['#title'])));
        }
        else {
          form_set_error(t("@fieldname required for completed transaction", array('@fieldname' => $fieldname)));
        }
      }
    }
  }
  if (count(form_get_errors())) return;
  //check that each user is permitted to use that currency
  $currency = currency_load($transaction->cid);
  foreach (array($transaction->payer, $transaction->payee) as $uid) {
    if (!currency_access('use', $currency, $uid)) {
      form_set_error('cid', t("!name is not permitted to use @currency", array(
        '!name' => theme('username', array('account' => user_load($transaction->payer))),
        '@currency' => $currency->name
      )));
    }
  }

  if ($transaction->payer && $transaction->payee) {
    //this concerns rules and database integrity, so we hand it off to the db controller
    //we're checking the balances, and trial-entering the transaction in the db
    $transaction = transaction_controller('validate', $transaction);
  }
}

/*
 * Submit callback for default transaction form
 */
function transaction_admin_form_submit($form, &$form_state) {
  if (!isset($form_state['redirect'])) {
    $form_state['redirect'] = 'transaction/'.$transaction->xid;
  }
}


/*
 * Couldn't be bothered to provide a theme callack for such a rarely used form.
 */
function mcapi_transaction_erase_confirm_form($form, $transaction){
  if ($form['#parameters'][2]->type == 'transaction') {
    drupal_set_title(t('Are you sure you want to erase this transaction?'));
    $form['#prefix'] = '<blockquote>' . $transaction->title . '</blockquote>';
    $form['description']['#value'] = t('This transaction was confirmed, so the balances of both users will be affected');
  }
}

/*
 * This is mostly here just to test the controller
 * Usually you would use views to list a users transactions
 * that's why there's no dedicated theme callback
 */
/*
function mcapi_statement($account) {
  $objects = transaction_controller('statement', $account->uid);
  foreach ($objects as $object) {
    $transaction = (array)$object;
    $transaction['payer'] = format_username(user_load($transaction['payer']));
    $transaction['payee'] = format_username(user_load($transaction['payee']));
    $transaction['creator'] = format_username(user_load($transaction['creator']));
    $transaction['modifier'] = format_username(user_load($transaction['modifier']));
    $transaction['description'] = l($transaction['description'], 'transaction/'. $transaction['xid']);
    unset($transaction['data']);
    $transactions[] = $transaction;
  }
  return theme('table', array('rows' => $transactions, 'header' => array_keys($transaction)));
}*/

/**
 * delete a single transaction
 */
function transaction_erase($xid) {
  $transaction = transaction_controller('load', array($xid));
  return transaction_controller('erase', $transaction);
}

/*
 * form element validation callback functions
 */

function mcapi_validate_title(&$element, $form_state) {
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

function mcapi_validate_quantity(&$element, &$form_state) {
  $value = $element['#value'] + 0;//convert it from string to a number
  //null values allowed
  if (!$value)return;

  if ($element['#value'] < 0 ) {
    form_error($element, t("Negative values not allowed for '@fieldname'", array('@fieldname' => $element['#title'])));
  }
  $fraction = fmod($value, 1) * 100;
  if ($fraction && isset($form_state['values']['cid']) && $currency = all_currencies($form_state['values']['cid'])) {
    if (is_null($currency->divisions) && !is_integer($value)) {
      form_error($element, t('%name must be a positive integer.', array('%name' => $currency->title)));
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

function mcapi_validate_state(&$element, $form_state) {
  if (!in_array($element['#value'], mcapi_transaction_states())) return;
  form_error($element, t('Invalid transaction state.') .' '. t('Suspected hacking attempt!'));
}
function mcapi_validate_ttype(&$element, $form_state) {
  // _element_validate() checks select widgets in the same way
  if (in_array( $element['#value'], module_invoke_all('transaction_info', 'types'))) return;
  form_error($element, t('Invalid transaction type.') .' '. t('Suspected hacking attempt!'));
}
/*
 * validate callback for user id fields, using
 */
function mcapi_validate_user(&$element, $form_state) {
  $values = is_array($element['#value']) ? $element['#value'] : array($element['#value']);
  if (empty($value)) return;
  foreach ($values as $value) {
    $account = user_load_by_name($value);
    if ($account->uid) continue;
    form_error($element, t('The username %name does not exist.', array('%name' => $value)));
  }
}
function mcapi_currency_validate_update_mode(&$element, $form_state) {
  if ($element['#value'] == 1 && $form_state['values']['delete_mode'] == 0) {
    form_error($element, t('Accounting standards') .' '. t('Update mode is incompatible with delete mode'));
  }
}


/*
 * When we need to know the currency divisions, this function converts them
 * from the multiline string of the configuration to a nice array
 * This could possibly be configured, stored and retrieved better
 * How does the list module do it?
 */
function _currency_divisions_array($string) {
  if (strpos($string, '|')) {
    $lines = explode("\n", $string);
    foreach($lines as $line) {
      $array = explode('|', $line);
      $allowed[$array[0]] = $array[1];
    }
    return $allowed;
  }
  elseif ($string == '00') {
    return '00';
  }
}


function theme_balances($variables) {
  $view = &$variables['view'];
  $handler = $view->style_plugin;
  $header = array();
  //the first column of the result is the currency id, which we need for the header.
  array_shift($view->style_plugin->options['columns']);
  $cid_alias = array_shift($view->field)->field_alias;
  //each column heading is a currency name
  $header = array('');
  foreach($variables['rows'] as $curr) {
   //wouldn't need to load it if views was linking the table properly
    $name = currency_load($curr->$cid_alias)->name;
    $header[] = $name;
  }
  foreach ($view->style_plugin->options['columns'] as $field) {
    $name = $view->field[$field]->field_alias;
    if (!empty($view->field[$field])) {
      $rows[$name][] = check_plain($view->field[$field]->label());
    }
    foreach($variables['rows'] as $row) {
      $rows[$name][$row->$cid_alias] =  $row->$name;
    }
  }
  return theme('table', compact('header', 'rows'));
}

/*
 * adds a running balance field 'balance' to an array of transactions
 * $offset is in fact a starting balance
 */
function transactions_totalise(&$transactions, $uid, $offsets) {
  //ensure every currency has a starting balance
  foreach (currencies_load() as $currency) {
    $totals[$currency->cid] = isset($offsets[$currency->cid]) ? $offsets[$currency->cid] : 0;
  }
  while (list($key, $transaction) = each($transactions)) {
    if (!isset($totals[$transaction->cid])) {
      $totals[$transaction->cid] = mcapi_controller('transaction', 'get_running_balance', array($transaction->xid, $uid, $transaction->cid));
    }
    if ($transaction->payer == $uid) {
      $totals[$transaction->cid] -= $transaction->quantity;
    }
    else {
      $totals[$transaction->cid] -= $transaction->quantity;
    }
    $transactions[$key]->balance = $totals[$transaction->cid];
  }
}
