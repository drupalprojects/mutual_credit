<?php
// $Id:
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
    case 'drush:mcapi-delete':
      return dt('Completely remove all references to transactions in state < TRANSACTION_STATE_PENDING');
    case 'drush:mcapi-purge':
      return dt('Remove all erased and reversed transactions and unfreeze the deletemode');
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
    'description' => dt('Trucate the transactions table and fieldAPI fields. USE WITH CARE.'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'options' => array(
      'purge' => 'Completely remove'
    ),
  );
  $items['mcapi-delete'] = array(
    'callback' => 'drush_mcapi_delete',
    'description' => dt('Selectively delete transactions. Pass the serial numbers, comma separated'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );
  $items['mcapi-purge'] = array(
    'callback' => 'drush_mcapi_purge',
    'description' => dt('Remove all erased and reversed transactions and unfreeze the deletemode'),
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
  if (variable_get('mcapi_controller', 'mcapi') != 'mcapi') {
    drush_print(dt('Purging only supported with default transaction engine.'));
    return;
  }
  if (module_exists('mcapi_signatures')) {
    db_query("TRUNCATE table {mcapi_signatures}");
  }
  //how to do "are you sure?" messages in drush!
  //this should be protected with the huser 1 password
  drush_print(dt("@purged transactions purged", array(
    '@purged' => count(db_query("SELECT count(serial) FROM {mcapi_transactions}")->fetchField()),
  )));

  foreach (field_read_instances(array('entity_type' => 'transaction', 'bundle' => 'transaction')) as $instance) {
    db_delete('field_data_'.$instance['field_name'])->condition('entity_type', 'transaction')->execute();
    db_delete('field_revision_'.$instance['field_name'])->condition('entity_type', 'transaction')->execute();
  }
  db_truncate('mcapi_transactions')->execute();
  db_truncate('cache_field')->execute();
  variable_set('change_undo_mode', TRUE);
}

function drush_mcapi_delete() {
  hard_delete(func_get_args());
  drush_print(dt('Deleted'));
}

function drush_mcapi_purge() {
  $serials = db_select('mcapi_transactions', 't')
    ->fields('t', array('serial'))
    ->condition(db_or()
      ->condition('state', TRANSACTION_STATE_UNDONE)
    )
  ->execute()->fetchCol();
  hard_delete($serials);
  variable_set('change_undo_mode', TRUE);
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

function hard_delete($serials) {
  foreach ($serials as $serial) {
    $transaction = transaction_load($serial);
    field_attach_delete('transaction', $transaction);
    module_invoke_all('entity_delete', $transaction, 'transaction');
    entity_get_controller('transaction')->undo($serial, TRUE);
  }
}