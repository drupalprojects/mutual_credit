<?php

/*
 * builds that part of the currency edit form which is common in all circumstances
 */
function _mcapi_currency_form(&$form, &$form_state) {
  //in case this is an ajax reload, set the currency object, used for defaults, with the form_state
  $form['#tree'] = TRUE;

  $form['name'] = array(
    '#title' => t('Name of currency'),
    '#description' => t('Use the plural'),
    '#type' => 'textfield',
    '#default_value' => $form['#currency']->name,
    '#weight' => -1
  );
  $form['format'] = array(
    '#title' => t('Display format'),
    '#description' => t('Write an expression to control the display of the currency using the following tokens:') .' @minus, @integer, @subdivision ' .
       t('For example to display -$45:23 AUS, enter @minus@integer:@subdivision AUS'),
    '#type' => 'textfield',
    '#default_value' => $form['#currency']->format,
    '#element_validate' => array('mcapi_currency_validate_format'),
    '#required' => TRUE,
    '#weight' => 3
  );
  $form['divisions_setting'] = array(
    '#title' => t('Allowed divisions'),
    '#description' => t('Leave blank to use integers. "00" to use hundredths.') . t('To use a dropdown, such as for quarters of an hour, write a "hundredths value| visible text" on each line. E.g. ') . '<br />' .
      '<pre>0|/.<br />25|1/4<br />50|1/2<br />75|3/4</pre>',
    '#type' => 'textarea',
    '#cols' => 60,
    '#rows' => 4,
    '#default_value' => $form['#currency']->divisions_setting,
    '#element_validate' => array('mcapi_currency_validate_divisions'),
    '#weight' => 5
  );

  $operations['view'] = t('View');
  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 15,
  );
  if (isset($form['#currency']->accounting)) { //not all engines will do this
    $form['accounting'] = array(
      '#title' => t('Accounting standards'),
      '#description' => t('More or less formal accounting standards can be determined.') .' '.
        t('Not all combinations are meaningful!'),
      '#type' => 'fieldset',
      '#weight' => -1,
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
        '#default_value' => $form['#currency']->accounting['update_mode'],
        '#weight' => 1,
        '#element_validate' => array('mcapi_currency_validate_update_mode'),
        '#ajax' => array(
          'callback' => 'currency_form_ajax',
          'wrapper' => 'mcapi-currency-form',
        ),
      ),
      'erase_mode' => array(
        '#title' => t('Delete mode'),
        '#type' => 'radios',
        '#options' => array(
          0 => t("Can't delete"),
          1 => t('Mark deleted'),
          2 => t('Remove all traces from the database (not from backups)'),
        ),
        '#default_value' => $form['#currency']->accounting['erase_mode'],
        '#weight' => 2,
        '#ajax' => array(
          'callback' => 'currency_form_ajax',
          'wrapper' => 'mcapi-currency-form',
        ),
      )
    );

    //alter the options according to what is set
    if ($form['#currency']->accounting['erase_mode'] == 0) {
      unset($form['accounting']['update_mode']['#options'][1]);
      if ($form['accounting']['update_mode']['#default_value'] == 1) {
        $form['accounting']['update_mode']['#default_value'] = 0;
      }
    }

    $operations['update'] = $form['accounting']['update_mode']['#default_value'] ? t('Update') : NULL;
    $operations['erase'] =  $form['#currency']->accounting['erase_mode'] ? t('Erase') : NULL;
  }
  //TODO: THESE AJAX CALLS ARE ONLY WORKING THE FIRST TIME BECAUSE THE FORM ID IS BEING INCREMENTD EACH TIME
  $form['membership_callback'] = array(
    '#title' => t('Membership'),
    '#description' => t('Determine which users are permitted to use this currency'),
    '#type' => 'select',
    '#options' => module_invoke_all('mcapi_access'),
    '#default_value' => $form['#currency']->membership_callback,
    '#weight' => 10,
  );
  $states = mcapi_transaction_states();
  unset($states['unset'], $states[-2]);
  $i = 0; $j = 0;
  $options = module_invoke_all('mcapi_permissions');
  foreach (array_filter($operations) as $op => $name) {
    $form[$op] = array(
      '#title' => $name,
      '#description' => t('Determine who can @name transactions in the following states:', array('@name' => t($name))),
      '#type' => 'fieldset',
      '#group' => 'additional_settings',
      '#weight' => $i++,
    );
    $defaults = $form['#currency']->$op;
    foreach ($states as $constant => $state) {
      $form[$op][$constant] = array(
        '#title' => $state,
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $defaults[$constant],
        '#weight' => $j++,
      );
    }
  }
  $form['buttons'] = array(
    '#weight' => 20,
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 20
    )
  );
}

function currency_form_ajax(&$form, &$form_state) {
  return $form;
}


/*
 * menu callback for editing a currency
 */
function mcapi_currency_form($form, &$form_state){
  form_load_include($form_state, 'admin.inc', 'mcapi', 'currencies');
  //by using a hook here instead of form_alter, we leave form_alter available for other
  $form['#currency'] = isset($form_state['values']) ? (object)$form_state['values'] : currency_default();
  $form['info']['currcode'] = array(
    '#type' => 'hidden',
    '#value' => empty($form['#currency']->info['currcode']) ? substr(variable_get('site_name', 'drupal7'), 0, 7) : $form['#currency']->info['currcode'],
  );
  _mcapi_currency_form($form, $form_state);
  return $form;
}

//validate is in currencies.admin.inc because it's the same for one or many currencies
/*
 * Submit callback for mcapi_currency_form
 * saves the currency as a variable
 */
function mcapi_currency_form_submit($form, $form_state) {
  //save the currency settings as a single nested array
  $values = $form_state['values'];
  unset($values['form_id'], $values['form_token'], $values['submit'], $values['form_build_id'], $values['buttons']);
  variable_set('currency_default', $values);
  currencies_recache();
}

/*
 * callback from ctools export_ui plugin
 */
function mcapi_currencies_form_submit($form, &$form_state) {
  currencies_recache();
  //I would have thought this database key would have been provided automacically
  //since the field was created automatically
  $form_state['item']->currcode = $form_state['values']['info']['currcode'];
  $form_state['item']->data = (object)$form_state['values'];
}


function mcapi_currency_validate_format(&$element, $form_state) {
  if (!strpos($element['#value'], '@integer')) {
    form_error($element, t("Currency format must contain token '@integer'"));
  }
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

/*
 * Validate callback for currency form 'update_mode' element
 */
function mcapi_currency_validate_update_mode(&$element, $form_state) {
  if ($element['#value'] == 1 && $form_state['values']['erase_mode'] == 0) {
    form_error($element, t('Accounting standards') .' '. t('Update mode is incompatible with delete mode'));
  }
}

/*
 * Called from the formAPI
 * Checks for duplicate currency names
 * may not be necessary with ctools
 */
function mcapi_currency_validate_name(&$element, $form_state) {
  $element['#value'] = check_plain($element['#value']);
  if (!module_exists('mcapi_currencies')) return;
  //should check for dupes but this is tricky actually given this is used for creating and editing.
  return; //will rely on drupal exception handling
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
 * Functions to handle the default currency, if mcapi_currencies isn't installed
 */
function currency_default() {
  module_load_include('inc', 'mcapi');
  $def = variable_get('currency_default', array(
    //default currency name is taken from the site_name at the moment it's first called
    'info' => array(//this structure anticipates ctools and mcapi_currencies.module
      'currcode' => substr(variable_get('site_name', 'blah'), 0, 8)
    ),
    'name' =>  t('Community Coins'),
    'format' => '@minus<strong>CC</strong>@integer:@subdivision',
    'divisions_setting' => "0|00\n50|30 mins",
    'accounting' => array(
      'update_mode' => 0,
      'erase_mode' => 0
    ),
    'creator' => 1,
    'membership_callback' => 'mcapi_use_currency',
    'view' => array(
      0 => 'mcapi_access_authenticated',
      -1 => 'mcapi_access_none'
    ),
  ));
  return (object)$def;
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
 * currency membership callback
 */
function mcapi_use_currency() {
  return user_access('transact');
}

//put the currencies back in the cache
function currencies_recache() {
  cache_clear_all('currencies', 'cache');

  $currency = currency_default();
  $currencies[$currency->info['currcode']] = $currency;

  if (module_exists('mcapi_currencies')) {
    $currencies = currencies_load_all();
    //set the max number of currencies you pan pay with on one form
    $field = field_read_field('worth');
    $field['cardinality'] = count($currencies);
    field_update_field($field);
  }
  foreach($currencies as $currcode => $currency) {
    $currency->divisions = _currency_divisions_array($currency->divisions_setting);
  }
  cache_set('currencies', $currencies);
  return $currencies;
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
