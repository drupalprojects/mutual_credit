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
  return $items;
}

//truncate transactions
function drush_mcapi_wipeslate() {
  $storage = \Drupal::entityManager()->getStorage('mcapi_transaction')->wipeslate();
  //how to do "are you sure?" messages in drush!
  //this should be protected with the user 1 password
  drush_print(dt("@purged transactions purged", array('@purged' => count($serials))));
  field_cache_clear();
}

//delete specific transactions by serials
function drush_mcapi_delete() {
	$storage = \Drupal::entityManager()->getStorage('mcapi_transaction');
	foreach(func_get_args() as $serial) {
    entity_load('mcapi_transaction', $serial)->delete();
	}
  drush_print(dt('Deleted'));
}
