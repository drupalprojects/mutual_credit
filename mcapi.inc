<?php
// $Id: mcapi.inc,v 1.1.2.7 2010/12/22 19:30:29 matslats Exp $


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
    'page arguments' => array('mcapi_admin_transaction_record_form'),
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
    'page arguments' => array('mcapi_admin_transaction_update_form', 1),
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
  $items['currency/%currencies/edit'] = array(
    'title' => 'Modify currency',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('currency_form', 1),
    'access callback' => array('currency_access'),
    'access arguments' => array('edit', 1),
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
  if ($GLOBALS['user']->uid == 1) {
    $page .= drupal_render(drupal_get_form('dev_tools_form'));
  }
  $page .= system_admin_menu_block_page();

  return $page;
}

function dev_tools_form() {
  $entity = entity_get_info('transaction');
  $form = array();
  $form['dev_tools'] = array(
    '#title' => t('Development tools'),
    '#description' => t('only visible to user 1 using the internal transaction engine.'),
    '#type' => 'fieldset'
  );
  if ($entity['controller class'] == 'TransactionEntityController') { //TODO test this
    $form['dev_tools']['refresh'] = array(
      '#type' => 'submit',
      '#description' => t('This may not be supported by the accounting engine'),
      '#value' => t("Recalulate balances"),
      '#submit' => array('mcapi_balances_refresh'),
      '#weight' => 5,
    );
  }
  return $form;
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
 * refresh all balances - needs to be retired
 */
function mcapi_balances_refresh() {
  db_query("TRUNCATE table {mcapi_cache}");
  //get a list of all the users and all the currencies they have traded in
  foreach (array_keys(currencies_load()) as $cid) {
    entity_get_controller('transaction')->balances_refresh($cid, get_trader_uids());
  }
  drupal_set_message(t('Flushed balance cache'));
  //need to tell the stats module to rebuild, but not worth making a hook
  //if module_exists('mc_stats')...
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
  $currencies = currencies_load();
  $header = array(t('Format'), t('Name'), t('Transactions'), t(''));
  $output = '';
  foreach ($currencies as $currency) {
    $row['format'] = theme('money', array('quantity' => -99, 'cid' => $currency->cid));
    $row['title'] = l($currency->name, 'currency/' . $currency->cid);
    $row['usage'] = db_select('mcapi_transactions')->condition('cid', $currency->cid)->countQuery()->execute()->fetchField();
    if (user_access('configure all currencies')) {
      $row['edit'] = l('edit', 'currency/' . $currency->cid . '/edit');
    }
    $rows[] = $row;
  }
  if (user_access('declare currency')) {
    $rows[]= array(NULL, NULL, NULL, l(t('Declare new currency'), 'currency/add'));
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows));

  return $output;
}

function currency_form($form, $form_state, $currency) {
  if (isset($currency->name)) $title = t("Edit currency '@name'", array('@name' => $currency->name));
  else $title = t('Declare currency');
  drupal_set_title($title);

  $form = array(
    'cid' => array(
      '#title' => t('Currency ID'),
      '#type' => 'textfield',
      '#size' => isset($currency->cid) ? strlen($currency->cid) : 1,
      '#default_value' => isset($currency->cid) ? $currency->cid : '',
      '#disabled' => TRUE,
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
  $form['accounting'] = array(
    '#title' => t('Accounting standards'),
    '#description' => t('More or less formal accounting standards can be determined.') .' '. t('Not all combinations are meaningful! The first fields take precedence'),
    '#type' => 'fieldset',
    '#weight' => 0,
    '#group' => 'additional_settings',
    'delete_mode' => array(
      '#title' => t('Delete mode'),
      '#type' => 'radios',
      '#options' => array(
        0 => t("Don't delete"),
        1 => t('Mark deleted'),
        2 => t('Remove all traces'),
      ),
      '#default_value' => isset($currency->delete_mode) ? $currency->delete_mode : 0,
      '#weight' => 1
    ),
    'update_mode' => array(
      '#title' => t('Update mode'),
      '#description' => t("Changing the state of a transaction doesn't count as editing it"),
      '#type' => 'radios',
      '#options' => array(
        0 => t("Don't update"),
        1 => t("Delete and create a new transaction"),
        2 => t("Update same record"),
        //3 => t("Use entity versioning the same transaction (not available)"),
      ),
      '#default_value' => isset($currency->update_mode) ? $currency->update_mode : 0,
      '#weight' => 2,
      '#element_validate' => array('mcapi_currency_validate_update_mode')
    ),
    'write_validate' => array(
      '#title' => t('Write transaction during validation'),
      '#description' => t('Transactions that are abandoned between validation and saving will leave a trace in the database.'),
      '#type' => 'checkbox',
      '#default_value' => isset($currency->write_validate) ? $currency->write_validate : 1,
      '#weight' => 3,
    ),
  );
  $form['limits'] = array(
    '#title' => t('Balance limits'),
    '#description' => t('Transactions will be rejected which take accounts beyond these limits.'),
    '#type' => 'fieldset',
    '#weight' => 1,
    '#group' => 'additional_settings',
    'min' => array(
      '#title' => t('Optional min balance'),
      '#description' => t('Must be less than or equal to zero'),
      '#type' => 'textfield',
      '#default_value' => isset($currency->min) ? $currency->min : 0,
      '#size' => 7,
      '#maxlength' => 7,
      '#attributes' => array('style' => 'width:inherit;')
     ),
     'max' => array(
      '#title' => t('Optional max balance'),
      '#description' => t('Must be greater than 0.'),
      '#type' => 'textfield',
      '#default_value' => isset($currency->max) ? $currency->max : 100,
      '#size' => 6,
      '#maxlength' => 6,
      '#weight' => 1,
      '#attributes' => array('style' => 'width:inherit;'),
    ),
    'limit_personal' => array(
      '#title' => t('Override'),
      '#description' => t("Limits are set per-user, on their profile form, by users with permission to '@permission'", array('@permission' => t('Administer users'))),
      '#type' => 'checkbox',
      '#default_value' => isset($currency->limit_personal) ? $currency->limit_personal : 0,
      '#size' => 6,
      '#maxlength' => 6,
      '#weight' => 2,
    )
  );
  $form['access'] = array(
    '#title' => t('Permissions'),
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
    'privacy' => array(
      '#title' => t('Visibility of transactions in this currency'),
      '#type' => 'radios',
      '#options' => array(
        0 => t('Only participants'),
        1 => t('Only participants & accountant'),
        2 => t("Users of this currency"),
        2 => t("All authenticated users"),
        3 => t('Public')
      ),
      '#default_value' => isset($currency->privacy) ? $currency->privacy : 2,
    ),
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

function currency_form_submit($form, $form_state) {
  mcapi_controller();
  $values = $form_state['values'];
  $values['data'] = array(
    'min' => $values['min'],
    'max' => $values['max'],
    'delete_mode' => $values['delete_mode'],
    'update_mode' => $values['update_mode'],
    'user_editable_mode' => $values['user_editable_mode'],
    'write_validate' => $values['write_validate'],
    'privacy' => $values['privacy'],
    'access_callback' => $values['access_callback']
  );
  unset($values['form_id'],
    $values['submit'],
    $values['form_token'],
    $values['form_build_it'],
    $values['op'],
    $values['update_mode'],
    $values['delete_mode'],
    $values['user_editable_mode'],
    $values['min'],
    $values['max'],
    $values['privacy'],
    $values['access_callback']
  );
  entity_get_controller('currency')->save((object)$values);
  if (user_access('configure all currencies')) {
    drupal_goto('admin/accounting');
  }
  else {
    drupal_goto('user');
  }
}

//return the max and min limits, by overriding currency limits with personal ones
//if $cid is not supplied an array of currencies is returned.
function user_limits($uid, $cid) {
  $balances = get_balances($uid, array($cid));
  $currency = currencies_load($cid);
  $limits[$cid] = array(
    $balances[$cid] = array(
      'min' => isset($balances[$cid]['min'])? $balances[$cid]['min'] : $currency->min[LANGUAGE_NONE][0]['value'],
      'max' => isset($balances[$cid]['max'])? $balances[$cid]['max'] : $currency->max[LANGUAGE_NONE][0]['value']
    )
  );
  return $limits;
}


/**
 * standard transaction form builder function. $currencies can be NULL, a $cid, or an array of currencies
 */
function mcapi_admin_transaction_record_form($form, $form_state, $currencies = array()) {
  //certain currencies may be visisble to the form, not necessarily all the currencies on the system
  //or one currency might be passed by the url
  //$form['#currencies'] = empty($currencies) ? currencies_load() : currency_choose('active', $GLOBALS['user']->uid);
  $transaction = new stdClass;
  if (count($currencies) == 1) {
    $transaction->cid = current($currencies)->cid;
  }
  transaction_form_build($form, $form_state, $transaction);
  $form['#submit'][] = 'transaction_form_submit';
  return $form;
}


/**
 * All transaction forms should use this function as a basis
 * $transaction connew stdClastains a transaction
 */
function transaction_form_build(&$form, $form_state, $transaction = NULL) {
  $xid = isset($transaction->xid) ? $transaction->xid : NULL;
  $currencies =  array(t('Choose currency...')) + currency_choose('active', $GLOBALS['user']->uid);

  $form['cid'] = array(
    '#title' => t('Currency'),
    '#description' => t('The ID of the currency'),
    '#type' => 'select',
    '#options' => $currencies,
    '#default_value' => isset($transaction->cid) ? $transaction->cid : '',
    '#weight' => 0,
    '#required' => TRUE,
    '#element_validate' => array('mcapi_validate_cid')
  );

  if (count($currencies) == 2) {
    unset($form['cid']['#options'][0]);
    $form['cid']['#disabled'] = TRUE;
  }

  $form['description'] = array(
    '#type' => 'textfield',
    '#title' => t('Reason for transaction'),
    '#default_value' => isset($transaction->description) ? $transaction->description : '',
    '#element_validate' => array('mcapi_validate_title'),
    '#weight' => 3,
    '#size' => 35
  );

  $form['payer_uid'] = array(
    '#type' => 'textfield',
    '#title' => t('Account to be debited'),
    '#default_value' => isset($transaction->payer_uid) ? $transaction->payer_uid : '',
    '#element_validate' => array('mcapi_validate_trader'),
    '#weight' => 6
  );
  $form['payee_uid'] = array(
    '#type' => 'textfield',
    '#title' => t('Account to be credited'),
    '#description' => t('A username, email, or user ID'),
    '#default_value' => isset($transaction->payee_uid) ? $transaction->payee_uid : '',
    '#element_validate' => array('mcapi_validate_trader'),
    '#weight' => 9
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
}


function mcapi_admin_transaction_update_form($form, $form_state, $transaction) {
  $cid = intval($transaction->cid);
  $form['#currencies'][$cid] = currencies_load($cid);
  transaction_form_build($form, $form_state, $transaction);
  $form['#submit'][] = 'transaction_form_submit';
  return $form;
}

/*
 * 
 * Prepares a transaction object in the $form_state
 */
function mcapi_prevalidate_transaction($form, &$form_state) {
//function prepare_transaction($payer_uid, $payee_uid, $quantity, $options) {
  global $user;
  $form_state['#transaction'] = (object)array(
    'payer_uid' => $form_state['values']['payer_uid'],
    'payee_uid' => $form_state['values']['payee_uid'],
    'quantity' => $form_state['values']['quantity'],
    'cid' => isset($form_state['values']['cid']) ? $form_state['values']['cid'] : array_pop(array_keys(currencies_load())),
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
  $transaction = &$form_state['#transaction'];

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
        form_set_error($fieldname, t("@fieldname required for completed transaction", array('@fieldname' => $form[$fieldname]['#title'])));
      }
    }
  }
  //check that each user is permitted to use that currency
  $currency = currencies_load($transaction->cid);
  foreach (array($transaction->payer_uid, $transaction->payee_uid) as $uid) {
    if (!currency_access('passive', $currency, $uid) || !currency_access('passive', $currency, $transaction->payee_uid)) {
      form_set_error('cid', t("@name is not permitted to use @currency", array(
        '@name' => theme_username(user_load($transaction->payer_uid)),
        '@currency' => $currency->name
      )));
    }
  }
  try{
    //this checking is done at the form validation level here, but maybe also by the database validate
    mcapi_check_limits($transaction, $transaction->state);
  }
  catch (Exception $e) {
    form_set_error('', $e);
  }
  
  if ($transaction->payer_uid && $transaction->payee_uid) {
    //this concerns rules and database integrity, so we hand it off to the db controller
    $transactionController = entity_get_controller('transaction');
    try {
      //we're checking the balances, and trial-entering the transaction in the db
      $transactionController->validate($transaction);
      //both should produce the same results, only one is necessary
    }
    catch (Exception $e) {
      user_access('manage all transactions') ? drupal_set_message($e, 'warning') : form_set_error('quantity', $e);
    }
  }
}

/*
 * Submit callback for default transaction form
 */
function transaction_form_submit($form, &$form_state) {
  entity_get_controller('transaction')->save($form_state['#transaction'], TRUE);

  $link = 'transaction/'.$form_state['#transaction']->xid;
  if (isset($form_state['#transaction']->xid)) {
    drupal_set_message(t('Transaction recorded.') .' '.
      l(t('edit'), $link .'/edit')
    );
  }
  if (!isset($form_state['redirect'])) $form_state['redirect'] = $link;
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

/*
 * limit checker
 * checks a transaction to see if the proposed difference will violate the balance limits
 * A pending transaction will be checked against the pending total
 * $transaction = A transaction object
 * $state = Whether to compare with finished or pending balances.
 */
function mcapi_check_limits($transaction, $state = TRANSACTION_STATE_FINISHED) {
  $cid = $transaction->cid;
  $payer_uid = $transaction->payer_uid;
  $payee_uid = $transaction->payee_uid;
  $difference = $transaction->amount;
  $message = '';

  //check that the payee isn't going over their max limit
  $balances = entity_get_controller('transaction')->get_balances($payee_uid, $cid);
  $payee_projected = $balances[$cid]['cleared_balance'] + $difference;
  if ($state == TRANSACTION_STATE_PENDING) {
    $payee_projected += $balances[$cid]['pending_dif'];
  }
  $max = $balances[$cid]['max'];
  if (is_numeric($max) && (float)$difference > 0) {
    $surplus = $payee_projected - $max;
    if ($surplus > 0) {
      $message .= t('Transaction takes @username !quant above the maximum balance of !max',
        array(
          '@username' => strip_tags(theme('username', array('account' => user_load($uid)))),
          '!quant' => theme('money', array('quantity' => $surplus, 'cid' => $cid, $richtext = FALSE)),
          '!max' => theme('money', array('quantity' => $max, 'cid' => $cid, $richtext = FALSE))
        )
      );
    }
  }
  //check that the payer isn't going below their min limit
  $balances = entity_get_controller('transaction')->get_balances($payer_uid, array($cid));
  $payer_projected = $balances[$cid]['cleared_balance'] + $difference;
  if ($state == TRANSACTION_STATE_PENDING) {
    $payer_projected += $balances[$cid]['pending_dif'];
  }
  $min = $balances[$cid]['min'];
  if (is_numeric($min) && (float)$difference < 0) {
    $deficit = $min - $payer_projected;
    if ($deficit > 0) {
      $message .= t('Transaction takes @username !quant below the minimum balance of !min',
        array(
          '@username' => strip_tags(theme('username', array('account' => user_load($payer_uid)))),
          '!quant' => theme('money', array('quantity' => $deficit, 'cid' => $cid, $richtext = FALSE)),
          '!min' => theme('money', array('quantity' => $min, 'cid' => $cid, $richtext = FALSE))
        )
      );
    }
  }
  if (strlen($message)) {
    throw new Exception($message);
  }
}
