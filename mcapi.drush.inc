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
  print_r(\Drupal\Core\Entity\EntityManagerInterface::getDefinitions('mcapi_transactions'));
  //drush isn't working yet, but when it is, we only show wipeslate if the transaction controller is Drupal\mcapi\TransactionStorageController

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

//trncate transactions
function drush_mcapi_wipeslate() {
  db_truncate('mcapi_transactions')->execute();
  db_truncate('mcapi_transactions_worths')->execute();
  db_truncate('mcapi_transactions_index')->execute();
  foreach (field_info_instances('mcapi_transaction', 'mcapi_transaction') as $fieldname => $info) {
    print_r($info);//find out what table to truncate.
  }
  //how to do "are you sure?" messages in drush!
  //this should be protected with the user 1 password
  drush_print(dt("@purged transactions purged", array('@purged' => count($serials))));
  field_cache_clear();
}
//delete transactions by serials
function drush_mcapi_delete() {
	$storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');
	foreach(func_get_args() as $serial) {
    mcapi_transaction_load($serial)->delete();
	}
  drush_print(dt('Deleted'));
}


//TODO rewrite this for D8
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
  $states = array_keys(mcapi_get_states(TRUE));
  $types = array('default');
  $currencies = currencies_load();
  unset($types['mass'], $types['dependent']);

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
    $cluster = array(entity_create('mcapi_transaction', $props));
    $serial = transaction_cluster_write($cluster, TRUE);
    $transaction = mcapi_transaction_load($serial);
    $renderable = transaction_view($transaction, \Drupal::config('mcapi.misc')->get('sentence_template'));
    drush_print(strip_tags(drupal_render($renderable)));
  }
}

