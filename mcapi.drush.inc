<?php

use \Drupal\mcapi\Entity\Transaction;

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
      return dt('Remove all erased and reversed transactions, reset the counters, and unfreeze the deletemode');
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
  );
  $items['mcapi-undo'] = array(
    //'callback' => 'drush_mcapi_undo',
    'description' => dt('Selectively delete transactions. Pass the serial numbers as individual arguments'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );
  $items['mcapi-delete'] = array(
    'callback' => 'drush_mcapi_delete',
    'description' => dt('Delete one or more sub-transactions, irrespective of the currency undo modes. Pass the entity id, xid, not the serial number!'),
    'drupal dependencies' => array('mcapi'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );
  return $items;
}

//truncate transactions
function drush_mcapi_wipeslate() {
  
  $storage = \Drupal::entityTypeManager()
    ->getStorage('mcapi_transaction')
    ->delete($storage->loadMultiple());
  
  //how to do "are you sure?" messages in drush!
  //this should be protected with the user 1 password
  drush_print(dt("@purged transactions purged", array('@purged' => count($serials))));
  field_cache_clear();
}

/**
 * drush callback
 * @note not yet implemented
 * undo specific transactions by serials
 * note that the currencies deployed affect whether the transaction is deleted, 
 * is reversed with another transaction, or simply changes state
 * @todo create a Transaction::undo function to sort this out, aside from what Transaction operations are doing.
 */
function drush_mcapi_undo() {
  //@todo check drush documentation for a better way to get the args
  //at least this way avoids loading them all into memory at once
	foreach(func_get_args() as $serial) {
    Transaction::loadBySerials(array($serial))->undo();
	}
  drush_print(dt('Deleted'));
}

/**
 * drush callback 
 * @todo test drush_mcapi_delete()
 */
function drush_mcapi_delete() {
  //todo I think there is a better way than func_get_args
  $transactions = Transaction::loadMultiple(func_get_args());
	foreach($transactions as $sub) {
    $sub->delete();
	}
}
