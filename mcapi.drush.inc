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
    case 'mcapi:mcapi-wipeslate':
      return dt('Undo all transactions according to the currency settings');
    case 'drush:mcapi-purge':
      return dt('Completely remove all references to transactions in state TRANSACTION_STATE_ERASED');
    case 'drush:mcapi-nodedel':
      return dt('remove the exchange nodes following the d6 upgrade');
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
  $items['mcapi-wipeslate'] = array(
    'callback' => 'drush_mcapi_wipeslate',
    'description' => dt('Undo all transactions according to the currency settings'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'options' => array(
      'purge' => 'Completely remove'
    ),
  );
  $items['mcapi-purge'] = array(
    'callback' => 'drush_mcapi_purge',
    'description' => dt('Completely remove all references to transactions and attached fields in state TRANSACTION_STATE_ERASED'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );
  $items['mcapi-nodedel'] = array(
    'callback' => 'drush_mcapi_delnodes',
    'description' => dt('remove the exchange nodes following the d6 upgrade'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUPAL_BOOTSTRAP_DATABASE,
  );
  return $items;
}


function drush_mcapi_wipeslate() {
  //how to do "are you sure?" messages in drush!
  $serials = db_query("SELECT serial FROM {mcapi_transactions}")->fetchCol();
  entity_get_controller('transaction')->state($serials, TRANSACTION_STATE_ERASED);
  $count = count($serials);
  drush_print("undone $count transactions");
  if (drush_get_option('purge')) {
    drush_mcapi_purge();
  }
}


function drush_mcapi_purge() {
  //how to do "are you sure?" messages in drush
  $transactions = transactions_load(NULL, array('state' => TRANSACTION_STATE_ERASED));
  entity_get_controller('transaction')->undo($transactions, MCPI_CURRENCY_UNDO_DELETE);
  foreach ($transactions as $transaction) {
    field_attach_delete('transaction', $transaction);
    module_invoke_all('entity_delete', $transaction, 'transaction');
  }
  $count = count($transactions);
  drush_print("completely removed $count transactions");
  if ($xid = db_query("SELECT xid FROM {mcapi_transactions}")->fetchfield()) return;
  
  //$fields = field_info_instance('transaction', 'field_worth', 'transaction');
  db_query("TRUNCATE TABLE {field_data_worth}");
  db_query("TRUNCATE TABLE {field_revision_worth}");
  db_query("TRUNCATE TABLE {mcapi_transactions}");
  variable_set('change_undo_mode', TRUE);
  drush_print('reset auto-increment-transaction ID on transaction & worth tables. This could cause problems with other fieldAPI fields');
}

//based on node_delete_multiple
function transaction_delete_multiple($serials) {
  if (empty ($serials)) return;
  $transactions = transactions_load(array(), array('serial' => $serials));
  foreach ($transactions as $transaction) {
    module_invoke_all('entity_delete', $transaction, 'transaction');
    field_attach_delete('transaction', $transaction);
  }
  db_delete('mcapi_transactions')->condition('serial', $serials)->execute();
}

function drush_mcapi_delnodes() {
  $nodes = db_query("SELECT nid FROM {node} WHERE type = 'exchange'")->fetchCol();
  $nids = implode(', ', $nodes);
  db_query("DELETE FROM {node} WHERE nid in ($nids)");
  db_query("DELETE FROM {node_revision} WHERE nid in ($nids)");
  db_query("DELETE FROM {node_access} WHERE nid in ($nids)");
  db_query("DELETE FROM {node_comment_statistics} WHERE nid in ($nids)");
  drush_print('Deleted all traces of exchange nodes from Drupal 6.');
}
