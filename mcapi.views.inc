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
  if (\Drupal::config('mcapi.wallet')->get('entity_types')['user:user'] == 1) {
    $data['users']['trade_stats'] = array(
      'title' => t('Transaction statistic'),
      'help' => t('The balance, trading volume, or transaction count for each user.'),
      'field' => array(
        'id' => 'mcapi_userstat',
      )
    );
  }
  $data['mcapi_transaction_worth']['worth']['field']['id'] = 'worth';
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

function mcapi_views_states() {
  foreach (\Drupal::config('mcapi.misc')->get('counted') as $key => $val) {
    if ($val) $options['value']['default'][$key] = $key;
  }
  return $options;
}

