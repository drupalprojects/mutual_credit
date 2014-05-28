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
function mcapi_drush_tester_help($section) {

  if (function_exists('devel_generate_drush_command') && $section == 'drush:generate-transactions') {
    return dt('Generate (10) transactions, random users, types, states, dates, currencies, quantities');
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
function mcapi_tester_drush_command() {
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


/**
 *
 */
function drush_devel_generate_transactions($num = 10) {
  mcapi_tester_make_transactions($num, FALSE);
  if ($num > 10) {
    mcapi_tester_make_transactions(intval($num/4), TRUE);
  }
}

