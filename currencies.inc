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
  $form['membership_callback'] = array(
    '#title' => t('Membership'),
    '#description' => t('Determine which users are permitted to use this currency'),
    '#type' => 'select',
    '#options' => module_invoke_all('mcapi_access'),
    '#default_value' => $currency->membership_callback,
    '#weight' => 3,
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
      CURRENCY_DIVISION_MODE_CENTS_INLINE => t('Cents in separate field'),
      CURRENCY_DIVISION_MODE_CENTS_FIELD => t('Cents in same field, with decimal point'),
      CURRENCY_DIVISION_MODE_CUSTOM => t('Allowed subdivisions, below')
    ),
    '#default_value' => $currency->display['divisions'],
    '#weight' => 2
  );
  $form['display']['divisions_allowed'] = array(
    '#title' => t('Allowed subdivisions'),
    '#description' => t('On each line put "hundredths| visible text". The visible text replaces the @subdivision in the display format, above.'),
    '#type' => 'textarea',
    '#cols' => 60,
    '#rows' => 4,
    '#resizable' => FALSE,
    '#default_value' => isset($currency->display['divisions_allowed']) ? $currency->display['divisions_allowed'] : "0|/.\n25|1/4\n50|1/2\n75|3/4",
    '#element_validate' => array('mcapi_currency_validate_divisions'),
    '#weight' => 3
  );

  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 10,
  );
  $operations['view'] = t('View');
  $form += currency_op_settings_tab($currency, $operations);
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

  //nasty design flaw around radio buttons which means ajax rebuilds them wrong
  if (arg(0) == 'system') {
    unset($form['accounting']['update_mode']['#title']);
  }

  if (isset($currency->currcode)) {
    $form['buttons']['submit']['#value'] = t('Update');
  }
}

function currency_op_settings_tab($currency, $operations) {
  static $i = 0;
  static $j = 0;
  foreach ($operations as $op => $name) {
    $element[$op] = array(
      '#title' => $name,
      '#description' => t('Determine who can @name transactions in the following states:', array('@name' => $name)),
      '#type' => 'fieldset',
      '#group' => 'additional_settings',
      '#weight' => $i++,
    );
    $defaults = $currency->$op;
    foreach (mcapi_states() as $state => $statename) {
      $element[$op][$state] = array(
        '#title' => $statename,
        '#type' => 'select',
        '#options' => module_invoke_all('mcapi_info_permissions'),
        '#default_value' => isset($defaults[$state]) ? $defaults[$state] : TRANSACTION_STATE_FINISHED,
        '#weight' => $j++,
      );
    }
  }
  return $element;
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
 * Validate callback for currency form 'update_mode' element
 */
function mcapi_currency_validate_update_mode(&$element, $form_state) {
  if ($element['#value'] == 1 && $form_state['values']['accounting']['erase_mode'] == 0) {
    form_error($element, t('Accounting standards') .' '. t('Update mode is incompatible with delete mode'));
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
 * determine whether a user has permission to use a currency
 * This could get rather complicated, and vary from system to system
 *
 */
function currency_access($op, $currency, $account = NULL) {
  if (empty($uid)) $uid = $GLOBALS['user'];
  switch ($op) {
    case 'use':
      $function = $currency->membership_callback;
      return $function($currency, $account);
    case 'edit':
      return $uid == $currency->uid || user_access('configure all currencies');
  }
}


/*
 * Implements hook_mcapi_access
 * List callbacks which can be used to determine whether a user can use a currency
 */
function mcapi_mcapi_access() {
  return array(
    'mcapi_use_currency' => t("According to permission '@perm'", array('@perm' => t('transact')))
  );
}

/*
 * currency membership callback
 */
function mcapi_use_currency() {
  return user_access('transact');
}



function transaction_validate_currency_access($form, $form_state) {
  $transaction = &$form_state['transaction'];
  $currency = currency_load($transaction->currcode);
  $participants = array('payer', 'payer');
  foreach ($participants as $participant) {
    if (!currency_access('use', $currency, $transaction->$participant)) {
      form_set_error($participant, t("!name is not permitted to use @currency", array(
        '!name' => format_username(user_load($transaction->$participant)),
        '@currency' => $currency->human_name
      )));
    }
  }
}


function currencies_load_all() {
  ctools_include('export');
  $states = mcapi_states();
  $functions = array_keys(module_invoke_all('mcapi_info_permissions'));
  $all = ctools_export_crud_load_all('mcapi_currencies');
  //unpack them - this is a bit of a shortcut, but ctools takes you right round the park
  foreach($all as $currcode => $currency_ctools_obj) {
    if (!empty($currency_ctools_obj->disabled)) continue;
    $currency = $currency_ctools_obj->data;
    //convert the divisions_allowed into an array
    $divisions = array();
    if ($currency->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM) {
      foreach(explode("\n", $currency->display['divisions_allowed']) as $line) {
        list($cent, $display) = explode('|', $line);
        $divisions[$cent] = $display;
      }
    }
    $currency->display['divisions_allowed'] = $divisions;
    //this is a good moment to check that all the currencies for all the operations, for all the states, have access callbacks
    foreach (array('update', 'erase', 'view') as $op) {
      $callbacks = $currency->$op;
      foreach ($states as $state => $name) {
        if (!in_array($callbacks[$state], $functions)) {
          drupal_set_message(t(
            "Currency @name has no permission set for op '@op' and state @state",
            array('@op' => $op, '@state' => $name, '@name' => $currcode)
          ), 'warning', FALSE);
          $callbacks[$state] = 'mcapi_access_participants';
        }
      }
    }

    $currencies[$currcode] = $currency;
  }
  return $currencies;
}
function mcapi_currency_form_validate(&$form, &$form_state) {
  
}

/*
 * Submit callback for mcapi_currency_form
 * saves the currency as a variable
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