<?php

/**
 * @file
 * Provide views data and handlers for mcapi.module.
 *
 * @ingroup views_module_handlers
 *
 */

/**
* implements hook_views_data_alter
*/
function mcapi_views_data_alter(&$data) {
  //todo only show this if max wallets per user = 1;
  $data['users']['trade_stats'] = array(
    'title' => t('Transaction statistic'),
    'help' => t('The balance, trading volume, or transaction count for each user. NB Assumes 1 wallet per user.'),
    'field' => array(
      'id' => 'mcapi_userstat',
    )
  );
  $data['users']['exchanges'] = array(
    'title' => t('Exchange'),
    'help' => t('Filter users by the exchange they are in.'),
    'argument' => array(
      'id' => 'user_exchanges',
    )
  );

}

/**
 * implements hook_form_FORM_ID_alter
 * renames the exposed filters on views forms
 */
function mcapi_form_views_exposed_form_alter(&$form, $form_state) {
  $description = t('Comma separated usernames');
  $form['payee']['#description'] = $description;
  $form['payer']['#description'] = $description;
}

