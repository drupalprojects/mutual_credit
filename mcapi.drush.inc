<?php

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
      return dt('Completely remove all references to transactions with given serial number');
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
    //'callback' => 'drush_mcapi_wipeslate',
    'description' => dt('Trucate the transactions table and fieldAPI fields. USE WITH CARE.'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'options' => array(
      'purge' => 'Completely remove'
    ),
  );
  $items['mcapi-delete'] = array(
    //'callback' => 'drush_mcapi_delete',
    'description' => dt('Selectively delete transactions. Pass the serial numbers as individual arguments'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );
  $items['mcapi-purge'] = array(
    //'callback' => 'drush_mcapi_purge',
    'description' => dt('Remove all erased and reversed transactions and unfreeze the deletemode'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );
  $items['mcapi-nodedel'] = array(
    //'callback' => 'drush_mcapi_nodedel',
    'description' => dt('remove the exchange nodes following the d6 upgrade'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUPAL_BOOTSTRAP_DATABASE,
  );
  if (function_exists('devel_generate_drush_command')) {
    $items['generate-transactions'] = array(
      //'callback' => 'drush_mcapi_purge',
      'description' => dt('Generate (10) transactions, random users, types, states, dates, currencies, quantities'),
      'drupal dependencies' => array('mcapi'),
      'arguments' => array(
        'num_transactions' => dt('Number of transactions to create. Defaults to 10.'),
      ),
      'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    );
  }
  return $items;
}


function drush_mcapi_wipeslate() {
	$storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');
	$serials = $storage->filter(array());
	foreach ($serials as $serial) {
		transaction_load($serial)->delete(MCAPI_UNDO_STATE_DELETE);
	}

  //how to do "are you sure?" messages in drush!
  //this should be protected with the user 1 password
  drush_print(dt("@purged transactions purged", array('@purged' => count($serials))));

  field_cache_clear();
  \Drupal::config('mcapi.misc')->set('change_undo_mode', TRUE);
}

function drush_mcapi_delete() {
	$storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');
	foreach(func_get_args() as $serial) {
    transaction_load($serial)->delete();
	}
  drush_print(dt('Deleted'));
}

function drush_mcapi_purge() {
	$storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');
	$serials = $storage->filter(array('state' => TRANSACTION_STATE_UNDONE));
	transaction_load($serial)->delete(MCAPI_UNDO_STATE_DELETE);
  \Drupal::config('mcapi.misc')->set('change_undo_mode', TRUE);
}

//TODO
//rewrite this for D8
function drush_devel_generate_transactions($num_transactions = 10) {
  //get all the possible variables
  $transact_uids = db_query(
    "SELECT uid from {users_roles} WHERE rid IN (:roles)",
    array(':roles' => array_keys(user_roles(FALSE, 'transact')))
  )->fetchCol();
  $transact_uids = array_flip($transact_uids);
  unset($transact_uids[0], $transact_uids[1], $transact_uids[variable_get('intertrading_uid', 0)]);
  $transact_uids = array_flip($transact_uids);

  drush_print('Available users are: '.implode($transact_uids));
  $states = array_keys(mcapi_get_states());
  $types = mcapi_get_types();
  $currencies = currencies_load();
  unset($types['remote'], $types['mass'], $types['dependent']);

  for ($i = 0; $i < $num_transactions; $i++) {
    $uids = $transact_uids;//make sure we don't pick the same uid for payer and payee
    shuffle($uids);
    $props = array(
      'payer' => array_pop($uids),
      'payee' => array_pop($uids),
      'type' => array_rand($types),
      'state' => array_rand($states),
      'quantity' => rand(1, 10),
      'currcode' => array_rand($currencies),
      'created' => rand(strtotime('-1 month'), REQUEST_TIME),
      'description' => 'randomly generated transaction'
    );
    $cluster = array(entity_create('transaction', $props));
    $serial = transaction_cluster_write($cluster, TRUE);
    $transaction = transaction_load($serial);
    $renderable = transaction_view($transaction, \Drupal::config('mcapi.misc')->get('sentence_template'));
    drush_print(strip_tags(drupal_render($renderable)));
  }
}

