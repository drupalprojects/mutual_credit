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
      'locale' => TRUE,
    ),
    'entity keys' => array(
      'id' => 'xid',
      //'bundle' => 'type',
      //'label' => 'label',
    ),
    'bundle keys' => array(
      'bundle' => 'type',
    ),
    'bundles' => array(
      'payment' => array(
        'label' => t('Transaction'),
        'admin' => array(
          //this is changed in mcapi_menu_alter
          'path' => 'admin/accounting/transaction/payment',
          'bundle argument' => 3,
          'access arguments' => array('configure currencies'),
        )
      ),
    ),
    'view modes' => array(
      'default' => array(
        'label' => t('Full content'),
        'custom settings' => FALSE,
      ),
      'sentence' => array(
        'label' => t('Sentence'),
        'custom settings' => FALSE,
      )
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
      //'bundle' => 'type',
      //'label' => 'label',
      //'revision' => 'revision',
    ),
    'bundle keys' => array(
      'bundle' => 'type',
    ),
    'bundles' => array(
      'mutual_credit' => array(
        'label' => t('Mutual credit'),
        'admin' => array(
          'path' => 'admin/accounting/currency/mutual_credit',
          'bundle argument' => 3,
          'access arguments' => array('configure currencies'),
        )
      )
    ),
    'view modes' => array(
      'default' => array(
        'label' => t('Currency page'),
        'custom settings' => FALSE,
      )
    )

  );

  // Search integration is provided by node.module, so search-related
  // view modes for nodes are defined here and not in search.module.
  //ummm...
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
    'description' => 'Manage currencies and transactions',
    'page callback' => 'mcapi_options',
    'access arguments' => array('configure currencies'),
    //'position' => 'right',
    'file' => 'mcapi.inc',
    'weight' => 2
  );
  $items['admin/accounting/acknowledgements'] = array(
    'title' => 'Acknowledgements',
    'description' => 'Help us to help you',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('cforge_report_form'),
    'access arguments' => array('configure currencies'),
    'file' => 'mcapi.inc',
    'weight' => 10
  );
  $items['admin/accounting/notification'] = array(
    'title' => 'Mail notification template',
    'description' => 'Compose the notification emails',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_notification_options'),
    'access arguments' => array('administer site configuration'),
    'file' => 'mcapi.inc',
    'weight' => 3
  );
  $items['admin/accounting/record'] = array(
    'title' => 'Record transaction',
    'description' => 'Set all the properties manually on a new transaction',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_admin_transaction_record_form'),
    'access callback' => 'transaction_access',
    'access arguments' => array('edit all transactions'),
    'file' => 'mcapi.inc',
  );
  $items['admin/accounting/transaction/%transaction/edit'] = array(
    'title' => 'Edit transaction',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('mcapi_admin_transaction_update_form', 3),
    'access callback' => 'transaction_access',
    'access arguments' => array('update', 3),
    'type' => MENU_CALLBACK,
    'file' => 'mcapi.inc',
  );
  $items['transaction/%transaction/erase'] = array(
    'title' => 'Erase transaction',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('transaction_erase_confirm_form', 1),
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
    'page arguments' => array('currency_add'),
    'access arguments' => array('declare currency'),
    'type' => MENU_CALLBACK,
    'file' => 'mcapi.inc',
  );
  $items['currency/%currency/edit'] = array(
    'title' => 'Modify currency',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('currency_edit', 1),
    'access callback' => array('mcapi_edit_currency_access'),
    'access arguments' => array(1),
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
  //run some checks
  if (!variable_get('mcapi_notification_subject', 0) || !variable_get('mcapi_notification_body', 0)) {
    drupal_set_message(t('Mail notification templates need to be written: !link', array('!link' => l('admin/accounting/notification', 'admin/accounting/notification'))), 'warning');
  }

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

function cforge_report_form($form) {
  $form = array(
    'cforge_report' => array(
      '#type' => 'radios',
      '#title' => t('Please report your site stats to Community Forge.'),
      '#default_value' => variable_get('cforge_report', TRUE),
      '#options' => array(
        0 => t('Send no data.'),
        1 => t('Report our site data to Community Forge only.'),
        2 => t('Report our site data to anyone who is interested')
      ),
      '#weight' => -5,
      '#description' => t("Be part of a wider network of local currency comunities by sharing a few key pieces of information with us.") . ' ' .
        t("No data about individual users or transactions will be sent.") . ' ' .
        t("e.g. site_name, site_url, number of active traders, number of transactions.") . ' ' .
        t("These stats motivate us and can be used to promote our work. We seek your trust and will not abuse this information.")
    )
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
  //need to tell the stats module to rebuild.
}

/*
 * implementation of mcapi hook after_transactions
 * sends the mail
 */
function mcapi_after_transaction($op, $transactions) {
  //decide whether to send mail, according to system and user settings.
  //for now we'll just mention the first transaction, but we'll need a theme callback to show how one transaction triggered the derivatives
  $transaction = array_shift($transactions);
  foreach (array(user_load($transaction->payer_uid), user_load($transaction->payee_uid)) as $account) {
    //first need to deterine whether to send
    //this depends on each user's threshhold but also on whether the mcapi_signatures module is installed.
    $message = drupal_mail(
      'mcapimail',
      'notify',
      $account->mail,
      user_preferred_language($account),
      array(
        'recipient' => $account,
        'transaction' => $transaction,
        'send' => FALSE
      ),
      NULL, //defualts to site_mail
      FALSE //don't send it
    );
    if ($message['params']['send']) {
      drupal_mail_send($message);
      //drupal_set_message('mailed '. $account->name .' with mail throttle setting '. $account->mcapi_notification_throttle);
    }
    else {
      //drupal_set_message("Didn't mail ". $account->name .' with mail throttle setting '. $account->mcapi_notification_throttle);
    }
  }
}
/*
 * Returns a form API item for choosing the intensity of transaction notifications
 */
function notification_throttle() {
  return array(
    '#title' => t('When to notify about transactions via email'),
    '#description' => t('Set the default frequency of notifications'),
    '#type' => 'radios',
    '#options' => array(
      0 => t("Never"),
      1 => t("Only when action is needed"),
      2 => t('When affected by another user'),
      3 => t('For all events'),
    ),
  );
}
/*
 * Define a form foro the admin to set the system-wide notification options.
 */
function mcapi_notification_options() {
  $form['mcapi_notification_throttle_default'] = notification_throttle();
  $form['mcapi_notification_throttle_default']['#weight'] = -6;
  $form['mcapi_notification_throttle_default']['#default_value'] = variable_get('mcapi_notification_throttle_default', 2);
  $form['mcapi_notification_option'] = array(
    '#title' => t('Allow user to choose notification frequency'),
    '#description' => t('Users will see the throttle below on user/%/edit page'),
    '#type' => 'checkbox',
    '#default_value' => variable_get('mcapi_notification_option', TRUE),
    '#weight' => -5
  );

  $form['mcapi_notification_wrapper'] = array(
    '#title' => t('Newly recorded transactions template'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => -4
  );
  $form['mcapi_notification_wrapper']['mcapi_notification_subject'] = array(
    '#title' => t('Mail subject'),
    '#description' => t('Tokens may be used'),
    '#type' => 'textfield',
    '#default_value' => variable_get('mcapi_notification_subject', 'Notification from !site'),
    '#weight' => 1,
    '#required' => TRUE
  );
  $form['mcapi_notification_wrapper']['mcapi_notification_body'] = array(
    '#title' => t('Mail body'),
    '#description' => t('Sent to payer or payee whenever an transaction is first recorded.') . ' ' . t('Tokens may be used.'),
    '#type' => 'textarea',
    '#default_value' => variable_get('mcapi_notification_body', "Dear !username,\n\nA new transaction has been recorded."),
    '#weight' => 2,
    '#required' => TRUE
  );
  //this might only appear if editing transactions is allowed.
  $form['mcapi_notification_update_wrapper'] = array(
    '#title' => t('Updated transactions template'),
    '#description' => t('(Not required for many use cases.)'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => -3
  );
  $form['mcapi_notification_update_wrapper']['mcapi_notification_update_subject'] = array(
    '#title' => t('Mail subject'),
    '#description' => t('Tokens may be used'),
    '#type' => 'textfield',
    '#default_value' => variable_get('mcapi_notification_update_subject', t('Notification from !site')),
    '#weight' => 1,
    '#required' => TRUE
  );
  $form['mcapi_notification_update_wrapper']['mcapi_notification_update_body'] = array(
    '#title' => t('Mail body'),
    '#description' => t('Sent to payer or payee whenever an transaction is changed.') .' '. t('Tokens may be used.'),
    '#type' => 'textarea',
    '#default_value' => variable_get('mcapi_notification_update_body', t("Dear !username,") . "\n\n" . t("An transaction involving you has been edited")),
    '#weight' => 2,
    '#required' => TRUE
  );

  $form['mcapi_notification_footer'] = array(
    '#title' => t('Mail footer'),
    '#description' => t('Sign off for all transaction notifications.') .' '. t('Tokens may be used'),
    '#type' => 'textarea',
    '#default_value' => variable_get('mcapi_notification_footer', t('From the team at !site')),
    '#required' => TRUE,
    '#weight' => -1
  );
  return system_settings_form($form);
}

function mcapimail_mail($key, &$message, $params) {
  $variables = mcapi_mail_tokens($params['recipient'], $message['language'], $params['transaction']);
  //the only $key possible is 'notify', so we don't need to switch($key)
  $subject = variable_get('mcapi_notification_subject', '');
  $message['subject'] = strtr($subject, $variables);
  $message['body']['main'] = strtr(variable_get('mcapi_notification_body', ''), $variables);
  $message['body']['footer'] = strtr(variable_get('mcapi_notification_footer', ''), $variables);
  $throttle = $message['params']['recipient']->mcapi_notification_throttle;
  //Setting of 2 means users don't want to be notified of their own activity.
  if ($message['params']['recipient']->uid == $GLOBALS['user']->uid) {
    $throttle--;
  }
  if ($throttle > 1) {
    $message['params']['send'] = TRUE;
  }
  debug($message);
}

function mcapi_mail_tokens($account, $language, $transaction) {
  //$account is the recipient of the mail
  $tokens = user_mail_tokens($account, $language);
  $creator = user_load($transaction->uid);
  $tokens['!transaction_uri'] = url('transaction/' . $transaction->nid, array('absolute' => TRUE));
  if (module_exists('mimemail')) {
    return $tokens + array(
      '!creator' => theme('username', array('account' => $creator)),
      '!summary' => theme('transaction', $transaction, TRUE, FALSE),
    );
  }
  else {
    return $tokens + array(
      '!creator' => $creator->name,
      '!summary' => strip_tags(theme('transaction', $transaction, TRUE, FALSE)),
    );
  }
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
    if (user_access('configure currencies')) {
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


/*
 * standard validation function for ALL transaction forms
 * Anything that validates here should work in the database
 * Even if the form wasn't properly designed in all respects.
 * This validation process allows NULL fields.
 * which also means it can validate partially completed forms
 */
function transaction_form_validate($form, &$form_state) {
  field_attach_form_validate('transaction', (object) $form_state['values'], $form, $form_state);
  $values = &$form_state['values'];

  if (!isset($values['type'])) {
    form_set_error('', t("Hidden field 'type' needs to be added to form. This value is intended to identify the module or form which created the transaction"));
    return;
  }
  if ($values['payer_uid'] && ($values['payer_uid'] == $values['payee_uid'])) {
    //no usecase is currently envisaged where someone might pay themselves
    form_set_error('', t('An transaction must involve two different users'));
    return;
  }

  //an transaction can only be complete if all these fields are filled in.
  if ($values['state'] == EXCHANGE_STATE_FINISHED) {
    if (!$values['quantity']) {
      form_set_error('quantity', t('Quantity required for completed transaction'));
    }
    if (!$values['description']) {
      form_set_error('description', t('Description required for completed transaction'));
    }
  }

  if ($values['payer_uid'] && $values['payee_uid']) {
    //the next validation concerns rules and database integrity, so we hand it off to the db controller
    try {
      //there are two approaches available here. this is the first
      entity_get_controller('transaction')->validate($values);
      //and this is the second  $transaction = build_transaction(
      $transaction = build_transaction(
        $form_state['values']['payer_uid'],
        $form_state['values']['payee_uid'],
        $form_state['values']['quantity'],
        $form_state['values']
      );
      entity_get_controller('transaction')->save($transaction, FALSE);
      //both should produce the same results, only one is necessary
    }
    catch (Exception $e) {
      user_access('edit all transactions') ? drupal_set_message($e, 'warning') : form_set_error('quantity', $e);
    }
  }
  elseif ($values['state'] == EXCHANGE_STATE_FINISHED) {
    form_set_error('payer_uid', t('Payee or payer required for completed transaction'));
    return;
  }
  module_invoke_all('transaction_form_validate', $form_state['values']);
  //plus don't forget the formcallback_validate function is called automatically
}

/*
 * Default submit callback for ALL transaction forms
 */
function transaction_form_submit($form, $form_state) {
  $transaction = build_transaction(
    $form_state['values']['payer_uid'],
    $form_state['values']['payee_uid'],
    $form_state['values']['quantity'],
    $form_state['values']
  );
  return entity_get_controller('transaction')->save($transaction, TRUE);
}
/*
 * Make an transaction entity object from the known fields
 * putting in defaults for any unknown values
 */
function build_transaction($payer_uid, $payee_uid, $quantity, $options) {
  global $user;
  //although the transaction entity tolerates it, this version of the API will not allow zero value transactions
  if (!$quantity) {
    drupal_set_message(t('Cannot create a zero value transaction'), 'error');
    return;
  }

  $transaction = $options + array(
    'payee_uid' => $payee_uid,
    'payer_uid' => $payer_uid,
    'quantity' => $quantity,
   //default $options
    'cid' => array_pop(array_keys(currencies_load())),
    'title' => format_date(REQUEST_TIME, 'short') . t('No details given'),  //shouldn't really be omitted
    'state' => EXCHANGE_STATE_FINISHED, //shouldn't really be omitted
    'type' => 'unspecified', //shouldn't really be omitted
    'uid' => $user->uid,
    'created' => REQUEST_TIME,
    'data' => array(),
  );

  $transaction['xid'] = isset($options['xid']) ? $options['xid'] : NULL;
  return (object) $transaction;
}


/**
 * All transaction forms should use this function as a basis
 */
function transaction_form_build(&$form, $form_state, $transaction) {
  if (!isset($transaction->type)) $transaction->type = 'default';
  //set the currency if there's only one to choose from, or it is given in the transaction
  if (isset($transaction->cid) && $transaction->xid) {
    if ($form['#currencies'][$transaction->cid]) {
      $form['cid'] = array(
        '#type' => 'hidden',
        '#value' => $transaction->cid,
      );
    }
    else {
      drupal_set_message(t('Given currency id is not valid: @id', array('@id' => $transaction->xid)), 'warning');
    }
  }
  else {
    $form['cid'] = array(
      '#title' => t('Currency'),
      '#description' => t('The ID of the currency'),
      '#type' => 'select',
      '#options' => mcapi_title_selector($form['#currencies'], 'name', t('Choose currency...')),
      '#weight' => 0,
      '#element_validate' => array('mcapi_validate_cid')
    );
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
  $form['state'] = array(
    '#type' => 'radios',
    '#title' => t('State'),
    '#description' => t('Finished transactions cannot have empty values.'),
    '#default_value' => isset($transaction->state) ? intval($transaction->state) : '0', //defaults to 0, or EXCHANGE_STATE_FINISHED
    '#options' => array(
      EXCHANGE_STATE_FINISHED => t('Finished'),
      EXCHANGE_STATE_PENDING => t('Pending'),
      EXCHANGE_STATE_ERASED => t('Erased'),
    ),
    //'#element_validate' => array('mcapi_validate_state'),
    '#weight' => 18
  );
  $form['type'] = array(
    '#type' => 'hidden',
    '#default_value' => $transaction->type,
  );
  field_attach_form('transaction', $transaction, $form, $form_state);
  $form['actions']['#weight'] = 25;

  if (isset($transaction->xid)) {
    $form['xid'] = array(
      '#type' => 'hidden',
      '#value' => $transaction->xid,
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
  $form['#validate'] = array('transaction_form_validate');
  $form['#submit'] = array('transaction_form_submit');
  $form['#redirect'] = 'user';
}


/**
 * standard transaction form builder function. $currencies can be NULL, a $cid, or an array of currencies
 */
function mcapi_admin_transaction_record_form($form, $form_state, $currencies = array()) {
  //certain currencies may be visisble to the form, not necessarily all the currencies on the system
  //or one currency might be passed by the url
  $form['#currencies'] = empty($currencies) ? currencies_load() : $currencies;
  if (count($form['#currencies']) == 1) {
    $currency = current($form['#currencies']);
    $transaction->cid = $currency->cid;
  }
  transaction_form_build($form, $form_state, new stdClass);
  return $form;
}

function mcapi_admin_transaction_update_form($form, $form_state, $transaction) {
  $cid = $transaction->cid;
  $form['#currencies'][$cid] = currencies_load($cid);
  transaction_form_build($form, $form_state, $transaction);
  return $form;
}



/*
 * Couldn't be bothered to provide a theme callack for such a rarely used form.
 */
function transaction_erase_confirm_form($form, $transaction){
  if ($form['#parameters'][2]->type == 'transaction') {
    drupal_set_title(t('Are you sure you want to erase this transaction?'));
    $form['#prefix'] = '<blockquote>' . $transaction->title . '</blockquote>';
    if ($transaction->state == EXCHANGE_STATE_PENDING) {
      $form['description']['#value'] = t('The transation had not been confirmed');
    }
    elseif ($transaction->state == EXCHANGE_STATE_FINISHED) {
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

/**
 * build a render array for the transaction
 * not sure if this is needed
 */
function transaction_view($transaction, $mode) {
  // Build fields content.
  field_attach_prepare_view('transaction', array($transaction->xid => $transaction), $mode);
  entity_prepare_view('transaction', array($transaction->xid => $transaction));
  $transaction->content += field_attach_view('postit', $postit, $view_mode);

  return $postit->content;

  $build = array(
    '#theme' => 'transaction',
    '#transaction' => $transaction,
    '#view_mode' => $mode,
  );
  return $build;
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
  if ($fraction && isset($form_state['values']['cid']) && $currency = currencies_load($form_state['values']['cid'])) {
    if (is_null($currency->fractions) && !is_integer($value)) {
      form_error($element, t("Integer values only for '@currency'", array('@currency' => $currency->title)));
    }
    elseif($currency->fractions == '00') {
      return;
    }
    elseif (isset($currency->fractions)) {
      if ($fraction && !isset($currency->fractions[$fraction])) {
        form_error($element, t("Invalid fraction for '@fieldname'", array('@fieldname' => $element['#title'])));
      }
    }
  }
}

function odd_cents($float) {
    $integer = intval($float);
    //format using the given currency subdivision
    $fraction = $float - $integer;
    return ceil($fraction * 100); //corrects the floating point error
}
/*
 * theme function to render an transaction briefly
 */
function theme_transaction_summary($variables) {
  $variables['view_mode'] = $variables['elements']['#view_mode'];

  $transaction = $variables['0'];
  $submitted = isset($transaction->created) ? $transaction->created : REQUEST_TIME;
  $replacements = array(
    '!amount' => theme('money', array('quantity' => $transaction->quantity, 'cid' => $transaction->cid)),
    '@title' => isset($transaction->nid) ? l($transaction->title, 'transaction/' . $transaction->xid . '/title') : $transaction->title,
    '!payer' => theme('username', array('account' => user_load($transaction->payer_uid))),
    '!payee' => theme('username', array('account' => user_load($transaction->payee_uid))),
    '@submitted' => format_date($submitted, 'short'),
  );
  switch ($transaction->state) {
    case EXCHANGE_STATE_PENDING:
      return t("On @submitted, !payer will pay !payee !amount for '@title'", $replacements);
    case EXCHANGE_STATE_FINISHED:
      return t("On @submitted, !payer gave !payee !amount for '@title'", $replacements);
    case EXCHANGE_STATE_DELETED:
      return t("On @submitted, !payer did not give !payee !amount for '@title'. (DELETED)'", $replacements);
  }
}

/*
 * make the transaction variables into something which can be nicely themed
 */
function mcapi_preprocess_transaction($transaction) {
  //created is only( blank if the transaction isn't actually created
  if (!isset($transaction->created)) {
    $transaction->created = REQUEST_TIME;
    $props['title_link'] = $transaction->title;
  }
  else {
    $props['title_link'] = l($transaction->title, '$transaction/' . $transaction->nid);
  }
  $props['submitted'] = format_date($transaction->created, 'short');
  $props['payer'] = theme('username', array('account' => user_load($transaction->payer_uid)));
  $props['payee'] = theme('username', array('account' => user_load($transaction->payee_uid)));
  $props['amount'] = theme('money', array('quantity' => $transaction->quantity, 'cid' => $transaction->cid));
  $props['rating'] = isset($transaction->rating) ? $transaction->rating : NULL;

  $props['classes'] = array();
  return $props;
}