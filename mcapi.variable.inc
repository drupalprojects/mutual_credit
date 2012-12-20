<?php

/*
 * Implements variable module's hook_variable_info
 */
function mcapi_variable_info() {
  $vars['mcapi_controller'] = array(
    'type' => 'select',
    'title' => t('Transaction entity controller'),
    'default' => 'mcapi',
    'description' => t('The module that controls how the transactions are read and written to the database.'),
    'required' => TRUE,
    'group' => 'mcapi',
    'type' => 'select',
    'options callback' => 'mcapi_variable_options_callback',
  );
  $vars['mcapi_deficit'] = array(
    'type' => 'array',
    'title' => t('Largest negative balances'),
    'default' => array('def_drup' => 0),
    'description' => t('Inelegant way of storing the lowest balances, to help the intertrading module survey the system health'),
    'required' => FALSE,
    'group' => 'mcapi',
  );
  $vars['transaction_description_field'] = array(
    'type' => 'select',
    'title' => t('Transaction description field'),
    'default' => '',
    'description' => t('Optional nominated fieldAPI field for describing the transaction'),
    'required' => FALSE,
    'group' => 'mcapi',
    'type' => 'select',
    'options callback' => 'mcapi_variable_options_callback',
  );
  $vars['mcapi_controller_delete_mode'] = array(
    'title' => t('Transaction delete mode'),
    'default' => 0,
    'description' => t('Whether transactions should be deleted, erased or offset'),
    'required' => FALSE,
    'group' => 'mcapi',
    'type' => 'select',
    'options callback' => 'mcapi_variable_options_callback',
  );
  $vars['mcapi_db_write'] = array(
    'type' => 'select',
    'title' => t('Transaction write Db'),
    'description' => t('Database connections for writing transactions (Default controller only)'),
    'required' => FALSE,
    'group' => 'mcapi',
    'type' => 'select',//this is multiple
    'options callback' => 'mcapi_variable_options_callback',
  );
  $vars['mcapi_db_read'] = array(
    'type' => 'select',
    'title' => t('Transaction read Db'),
    'description' => t('Database connection for reading transactions (Default controller only)'),
    'required' => FALSE,
    'group' => 'mcapi'
  );
  $vars['mcapi_sentence'] = array(
    'type' => 'string',
    'title' => t('Transaction sentence template'),
    'required' => TRUE,
    'group' => 'mcapi'
  );
  return $vars;
}

function mcapi_variable_options_callback() {
  drupal_set_message('is this worth implementing?');
}
