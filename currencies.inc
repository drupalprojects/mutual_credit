<?php

function _mcapi_currency_form(&$form, &$form_state, $currency){
  //in case this is an ajax reload, set the currency object, used for defaults, with the form_state
  $form['#tree'] = TRUE;
  $form['info']['currcode']['#maxlength'] = 8;
  $form['human_name'] = array(
    '#title' => t('Name of currency'),
    '#description' => t('Use the plural'),
    '#type' => 'textfield',
    '#default_value' => $currency->human_name,
    '#weight' => -1,
  );
  //need to do a live machine name preview here, like views does
  //http://api.drupal.org/api/drupal/includes%21form.inc/function/form_process_machine_name/7

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
  $currency_access_callbacks = module_invoke_all('currency_access_callbacks');
  $form['currency_access'] = array(
    '#title' => 'Currency access',
    '#description' => t('Check fewer boxes for efficiency'),
    '#type' => 'fieldset',
    '#group' => 'additional_settings',
    'membership' => array(
      '#title' => t('Use currency'),
      '#description' => t('Determine which users are permitted to use this currency'),
      '#type' => 'radios',
      '#options' => $currency_access_callbacks,
      '#default_value' => $currency->currency_access['membership'],
      '#weight' => 1,
    '#prefix' => '<style>.form-type-checkboxes .form-type-checkbox{margin:0;} .form-type-checkbox input{margin:0; padding:0}</style>',
    ),
    'user_aggregated_view' => array(
      '#title' => t('View aggregated user transaction data'),
      '#description' => t('Such as balance, gross income, number of transactions'),
      '#type' => 'radios',
      '#options' => $currency_access_callbacks,
      '#default_value' => $currency->currency_access['user_aggregated_view'],
      '#weight' => 2,
    ),
    'currency_aggregated_view' => array(
      '#title' => t('View aggregated system data'),
      '#type' => 'radios',
      '#options' => $currency_access_callbacks,
      '#default_value' => $currency->currency_access['currency_aggregated_view'],
      '#weight' => 3,
    ),
    'undo' => array(
      '#title' => t('Undo any transaction in this currency'),
      '#type' => 'radios',
      '#options' => $currency_access_callbacks,
      '#default_value' => $currency->currency_access['undo'],
      '#weight' => 3,
    ),
    '#weight' => -1
  );

  static $i = 0;
  static $j = 0;
    $form['transaction_access'] = array(
      '#title' => t('Transaction access'),
      '#description' => t('Determine who can view transactions in each state.') .' '. t('Any the checked conditions must return TRUE'),
      '#type' => 'fieldset',
      '#group' => 'additional_settings',
    );
    foreach (mcapi_get_states() as $constant => $state) {
      $states = isset($currency->view) ? $currency->view : array();
      $form['transaction_access'][$constant] = array(
        '#title' => $state['name'],
        '#description' => $state['description'],
        '#type' => 'checkboxes',
        '#options' => module_invoke_all('transaction_access_callbacks'),
        '#default_value' => $currency->transaction_access[$constant],
        '#weight' => $j++,
      );
    }

  $form['zero'] = array(
    '#title' => t('Allow zero value transactions'),
    '#description' => t('Some projects may want to record an acknowledgement without recording a transfer of value. Also if a transaction has multiple currencies, some of them may be zero for some transactions.'),
    '#type' => 'checkbox',
    '#default_value' => $currency->zero,
    '#weight' => 15,
  );
  $form['buttons'] = array(
    '#weight' => 20,
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 20
    )
  );
  $form['uid'] = array(
    '#type' => 'textfield',
    '#title' => t('Declared by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => $currency->uid ? user_load($currency->uid)->name : $GLOBALS['user']->name,
    '#element_validate' => array('mcapi_validate_usernames'),
    '#required' => TRUE,
    '#weight' => 4
  );

  if (isset($currency->currcode)) {
    $form['buttons']['submit']['#value'] = t('Update');
  }
}


function currency_form_ajax(&$form, &$form_state) {
  return $form;
}

function mcapi_currency_validate_format(&$element, $form_state) {
  if (!strpos($element['#value'], '@integer')) {
    form_error($element, t("Currency format must contain token '@integer'"));
  }
  //we also need to check that the format field contains the divisions token
  if (!empty($form_state['values']['divisions_allowed']) && !strpos($element['#value'], '@subdivision')) {
    form_error($element, t("Currency format must contain token '@subdivision'"));
  }
}

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
 * Implements hook_mcapi_access
 * List callbacks which can be used to determine whether a user can use a currency
 * These callbacks, at the moment take no arguments, and check access for the current user
 */
function mcapi_currency_access_callbacks() {
  return array(
    'mcapi_access_anyone' => t('Anyone, including anonymous users'),
    'user_access_transact' => t("According to permission '@perm'", array('@perm' => t('transact'))),
    'user_access_manage_all_transactions' => t("According to permission '@perm'", array('@perm' => t('Manage all transactions')))
  );
}

/*
//this function is no longer called. and doesn't make much sense anyway
function transaction_validate_currency_access($form, $form_state) {
  $transaction = (object)$form_state['values'];
  $currency = currency_load($transaction->currcode);
  $participants = array('payer', 'payer');
  foreach ($participants as $participant) {
    if (!currency_access('membership', $currency)) {
      form_set_error($participant, t("!name is not permitted to use @currency", array(
        '!name' => format_username(user_load($transaction->$participant)),
        '@currency' => $currency->human_name
      )));
    }
  }
}
*/

function currencies_load_all() {
  ctools_include('export');
  $functions = array_keys(module_invoke_all('transaction_access_callbacks'));
  $all = ctools_export_crud_load_all('mcapi_currencies');
  //unpack them - this is a bit of a shortcut, but ctools takes you right round the park
  foreach($all as $currcode => $currency_ctools_obj) {
    if (!empty($currency_ctools_obj->disabled)) continue;
    $currency = $currency_ctools_obj->data;
    //convert the divisions_allowed into an array
    $divisions = array();
    if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM) {
      foreach(explode("\n", $currency->display['divisions_setting']) as $line) {
        list($cent, $display) = explode('|', $line);
        $divisions[$cent] = $display;
      }
    }
    $currency->display['divisions_allowed'] = $divisions;
    //this is a good moment to check that all the currencies for all the operations, for all the states, have access callbacks
    foreach (mcapi_get_states('#full') as $constant => $state_info) {
      if (!isset($currency->transaction_access[$constant])) {
        unset($currency->transaction_access[$constant]);
        drupal_set_message(t('invalid access callback for currency @currname state @state.',
          array('@currname' => $currency->human_name, '@state' => $state_info['name'])
        ));
        $currency->transaction_access[$constant] = drupal_map_assoc($state_info['default_access_callbacks']);
      }
    }
    $currencies[$currcode] = $currency;
  }
  return $currencies;
}
//load the default currency and clear some fields, so it can populate the edit form with some discrete defaults
function default_cur() {
  $currency = ctools_export_crud_load('mcapi_currencies', 'def_drup')->data;
  $currency->human_name = '';
  $currency->display['format'] = '@minus@integer @subdivision';
  return $currency;
}

/*
 * Validate callback for mcapi_currency_form
 * declared in plugins/export/mcapi_ui
 * set the $form_state['item'] for ctools to save
 */
function mcapi_currency_form_validate(){
  
}
/*
 * Submit callback for mcapi_currency_form
 * declared in plugins/export/mcapi_ui
 * set the $form_state['item'] for ctools to save
 */
function mcapi_currency_form_submit($form, $form_state) {
  form_state_values_clean($form_state);
  //I would have thought this database key would have been provided automacically
  //since the field was created automatically
  if (empty($form_state['item']->currcode)) {
    $form_state['item']->currcode = $form_state['values']['info']['currcode'];
  }
  $form_state['item']->data = (object)$form_state['values'];
  register_shutdown_function('currencies_load_refresh');
}

//utility callback (takes no args)
function currencies_load_refresh() {
  currencies_load(NULL, TRUE);
}