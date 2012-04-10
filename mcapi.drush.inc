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
      return dt('Erase all transactions according to the currency settings');
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
    'description' => dt('Erase all transactions according to the currency settings'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'options' => array(
      'purge' => 'Completely remove'
    ),
  );
  $items['mcapi-purge'] = array(
    'callback' => 'drush_mcapi_purge',
    'description' => dt('Completely remove all references to transactions in state TRANSACTION_STATE_ERASED'),
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
  $xids = db_query("SELECT xid FROM {mcapi_transactions}")->fetchCol();
  $transactions = transaction_load_multiple($xids);
  foreach ($transactions as $transaction) {
    transaction_erase($transaction);
  }
  $count = count($xids);
  drush_print("erased $count transactions");
  if (drush_get_option('purge')) {
    drush_mcapi_purge();
  }
}


function drush_mcapi_purge() {
  //how to do "are you sure?" messages in drush!
  $xids = db_query("SELECT xid FROM {mcapi_transactions} WHERE state = :state", array(':state' => TRANSACTION_STATE_ERASED))->fetchCol();
  transaction_delete_multiple($xids);
  $count = count($xids);
  drush_print("completely removed $count transactions");
  if ($xid = db_query("SELECT xid FROM {mcapi_transactions}")->fetchfield()) return;
  db_query("ALTER TABLE {mcapi_transactions} AUTO_INCREMENT = 1");
  drush_print('reset auto-increment-transaction ID');
  
}

//based on node_delete_multiple
function transaction_delete_multiple($xids) {
  if (empty ($xids)) return;
  $transactions = transaction_load_multiple($xids);
  foreach ($transactions as $transaction) {
    module_invoke_all('entity_delete', $transaction, 'transaction');
    field_attach_delete('transaction', $transaction);
  }
  db_delete('mcapi_transactions')->condition('xid', $xids)->execute();
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
