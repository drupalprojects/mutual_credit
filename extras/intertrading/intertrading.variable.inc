<?php


/*
 * Implements variable module's hook_variable_info
 * only block settings really
 */
function intertrading_variable_info() {
  $vars['intertrading_server'] = array(
    'title' => t('Intertrading server url'),
    'description' => t('The url of the server. Contact the server administrator to change this'),
    'type' => 'string',
    'default' => '',
    'group' => 'mcapi',
    'required' => TRUE,
  );
  $vars['intertrading_ticks'] = array(
    'title' => t('Ticks'),
    'description' => t('The value of the currency, relative to a common denominator.') . '<br />'.
      t('If 1 tick is 1 minute and your currency is hours, then enter 60'),
    'type' => 'string',
    'default' => '10',
    'group' => 'mcapi',
    'required' => TRUE,
  );
  $vars['intertrading_currcode'] = array(
    'title' => t('Currency used for intertrading'),
    'type' => 'string',
    'group' => 'mcapi',
    'default' => '',
    'required' => TRUE,
  );
  $vars['intertrading_uid'] = array(
    'title' => t('The account on this exchange used for intertrading'),
    'description' => t('Enter the account number'),
    'type' => 'string',
    'default' => '',
    'group' => 'mcapi',
    'required' => TRUE,
  );
  return $vars;
}