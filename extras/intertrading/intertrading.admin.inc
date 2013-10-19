<?php


/*
 * menu callback
 */
function statement() {
  $data = _intertrading_handshake() + _intertrading_report();
  return theme('statement', array('data' => $data));
}


//this could be made prettier
function theme_statement($variables) {
  $currencies = currencies_load();
  $data = &$variables['data'];
  unset($data['api'], $data['divisions'], $data['mail'], $data['name'], $data['logo'], $data['first_trade']);
  $currcode = variable_get('intertrading_currcode', key($currencies));

  $rows = array();
  $rows['currency_name'] = array(
    'title' => t('Name of intertrading currency'),
    'value' => currency_load($currcode)->human_name
  );
  $rows['lat']['title'] = t('Latitude');
  $rows['lon']['title'] = t('Longitude');
  $rows['ticks']['title'] = t('Relative value');

  $rows['integrity'] = array(
    'title' => t('Balances add up to zero'),
    'value' => $data['integrity'] == TRUE ? t('Yes') : t('No')
  );
  $rows['traders']['title'] = t('Number of traders');
  $rows['transactions']['title'] = t('Last 30 days transactions');
  $rows['volume'] = array(
    'title' => t('Last 365 days volume'),
    'value' => theme('worth_item', array('currcode' => $currcode, 'quantity' => $data['volume'])),
  );
  $rows['deficit'] = array(
    'title' => t('Largest negative balance'),
    'value' => theme('worth_item', array('currcode' => $currcode, 'quantity' => $data['deficit']))
  );
  $rows['balance'] = array(
    'title' => t('Balance of Trade'),
    'value' => theme('worth_item', array('currcode' => $currcode, 'quantity' => $data['balance']))
  );
  $rows['visibility'] = array(
    'title' => t('Visibility'),
    'value' => $data['visibility'] == 0 ? t('Public') : t('Not public'),
  );
  //fill in a load of defaults and anything we missed out
  foreach ($data as $key => $prop) {
    $rows[$key] += array('value' => $prop);
  }

  return theme('table', array('rows' => $rows));
}


/*
 * menu callback
 * this could be one of three forms, depending on whether the site is set up
 */
function intertrading_settings() {
  form_load_include($form_state, 'inc', 'mcapi');
  form_load_include($form_state, 'admin.inc', 'intertrading');
  $form['intertrading_server'] = array(
    '#title' => t('Intertrading server'),
    '#description' => t('The url of the server.') .'<br />'.
      t('WARNING. This cannot and should not be easily changed once your account on the intertrading server is created'),
    '#field_prefix' => 'http://',
    '#type' => 'textfield',
    '#default_value' => variable_get('intertrading_server', 'clearingcentral.communityforge.net'),
    '#disabled' => variable_get('intertrading_server', 0),
    '#required' => TRUE
  );

  $currencies = currencies_load();
  $form['intertrading_currcode'] =  currency_picker_element(array_keys($currencies), variable_get('intertrading_currcode', 0));
  //strictly speaking the list of available users depends on the currency having been set already
  //get all the users who haven't traded yet
  $users = db_query("SELECT DISTINCT u.uid, name
    FROM {users} u
    LEFT JOIN {users_roles} ur ON u.uid = ur.uid
    LEFT JOIN {role_permission} rp ON ur.rid = rp.rid
    WHERE rp.permission = 'transact'")->fetchAllKeyed(0);
  $transactors = db_query("SELECT DISTINCT uid1
    FROM {mcapi_index}
    WHERE currcode = :currcode",
    array(':currcode' => variable_get('intertrading_currcode', 0))
  )->fetchCol();

  $account = user_load(variable_get('intertrading_uid', 0));
  $form['intertrading_uid'] = array(
    '#title' => t('Account for external transactions'),
    '#description' => t('WARNING. Once set, this cannot be changed.') .' '.
      t('Please ensure the mail for this account goes to a responsible person: @mail',
        array('@mail' => l($account->mail, "user/$account->uid/edit"))
      ),
    '#type' => 'select',
    '#options' => array_diff_key($users, array_keys($transactors)),
    '#default_value' => variable_get('intertrading_uid', 0),
    '#disabled' => $account->uid && transaction_filter(array('involving' => variable_get('intertrading_uid', 0))),
    '#required' => TRUE
  );

  $form['intertrading_ticks'] = array(
    '#title' => t('Ticks'),//this is a reference to the forex base comparision unit
    '#description' => t('The value of the currency, relative to a common denominator.') . '<br />'.
      t('If 1 tick is 1 minute and your currency is hours, then enter 60') . '<br />' .
      t('WARNING. This cannot and should not be easily changed once your account on the intertrading server is created'),
    '#type' => 'textfield',
    '#element_validate' => array('intertrading_validate_ticks'),
    '#default_value' => variable_get('intertrading_ticks', 0),
    '#disabled' => variable_get('intertrading_ticks', 0),
    '#required' => TRUE,
    '#weight' => 2
  );

  //if the intertrading account has traded, then all these fields should be disabled.
  if ($currcode = variable_get('intertrading_currcode')) {
    $balances = transaction_totals($account->uid, variable_get('intertrading_currcode'));
    if ($balances->count) {
      $form['intertrading_server']['#disabled'] = TRUE;
      $form['intertrading_ticks']['#disabled'] = TRUE;
      $form['intertrading_uid']['#disabled'] = TRUE;
    }
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Handshake with server'),
    '#weight' => 10
  );
  $form['#validate'] = array('intertrading_settings_validate');
  $form['#submit'] = array('intertrading_settings_submit', 'menu_rebuild', 'intertrading_handshake');
  return $form;
}

function intertrading_settings_validate(&$form, &$form_state) {
  $ticks = &$form_state['values']['intertrading_ticks'];
  if (is_numeric($ticks) && $ticks <= 0) {
    form_error($form['intertrading_ticks'], t('Ticks must be greater than zero'));
  }
}

function intertrading_settings_submit($form, $form_state) {
  module_load_include('variable.inc', 'intertrading');
  $vars = array_keys(intertrading_variable_info());//or could use form_state_clean function
  foreach ($vars as $var) {
    variable_set($var, $form_state['values'][$var]);
  }
}


function intertrading_validate_ticks(&$element, $form_state) {
  if (!is_numeric($element['#value'])) {
    form_error($element, t('Ticks must be numeric'));
  }
}
