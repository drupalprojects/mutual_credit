<?php


/**
 * @file
 * Views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function mcapi_limits_views_data_alter(&$data) {
  $data['users']['limits'] = array(
    'title' => t('Balance limits'),
    'help' => t("The trading limits for a user in a currency or currencies"),
    'field' => array(
      'id' => 'mcapi_limits',
    ),
  );
}
