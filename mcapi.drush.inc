<?php
// $Id: variable.drush.inc,v 1.19 2010/03/25 02:48:17 weitzman Exp $

/**
 * Implementation of hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 *
 * @param
 *   A string with the help section (prepend with 'drush:')
 *
 * @return
 *   A string with the help text for your command.
 */
function mcapi_drush_help($section) {
  switch ($section) {
    case 'drush:mcapi-balances':
      return dt("rebuild the mc_cache table");
    case 'drush:mcapi-wipeslate':
      return dt("erase all transactions");
    case 'drush:mcapi-purge':
      return dt("delete all erased transactions");
    case 'drush:mcapi-jubilee':
      return dt("rebalance the system (not implemented)");
  }
}


/**
 * Implementation of hook_drush_command().
 *
 * In this hook, you specify which commands your
 * drush module makes available, what it does and
 * description.
 *
 * Notice how this structure closely resembles how
 * you define menu hooks.
 *
 * @return
 *   An associative array describing your command(s).
 */
function mcapi_drush_command() {
  $items['mcapi-balances'] = array(
    'callback' => 'drush_mcapi_balances',
    'description' => dt('Truncate the mc_cache table and rebuild it by adding up all transactions'),
  );
  $items['mcapi-wipeslate'] = array(
    'callback' => 'drush_mcapi_wipeslate',
    'description' => dt('Erase all transactions (remove the row in the node table and set state to -1)'),
  );
  $items['mcapi-purge'] = array(
    'callback' => 'drush_mcapi_purge',
    'description' => dt('Delete all transactions in state -1'),
  );
  return $items;
}

function drush_mcapi_balances() {
  db_query("TRUNCATE table {mc_cache}");
  module_load_include('install', 'mcapi');
  mc_balances_refresh();
}

function drush_mcapi_wipeslate($hard = TRUE) {
  global $user;
  if ($user->uid == 0) {
    drush_print('User 0 cannot delete exchanges');
    return;
  }
  if ($hard) {
    $result = db_query("SELECT nid FROM {node} WHERE type = 'exchange'");
    while($nid = db_result($result)) {
      $nids[] = $nid;
      node_delete($nid);
    }
  }
  $count = count($nids);
}

function drush_mcapi_purge() {
  $result = db_query("DELETE FROM {mc_exchanges} WHERE state = %d", EXCHANGE_STATE_ERASED);
  drush_print('removed traces of erased nodes');
}
