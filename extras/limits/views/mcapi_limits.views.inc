<?php
//$Id:
/**
 * @file
 * Contains default views for the mcapi module.
 */

/*
 * implements views hook_views_data
 */
function mcapi_limits_views_data() {
  $data['mcapi_cache']['min'] = array(
    'field' => array(
      'title' => t('Minimum limit'),
      'help' => t("The minimum balance for a user in a currency"),
      'handler' => 'views_handler_field_mcapi_min',
    ),
  );
  $data['mcapi_cache']['max'] = array(
    'field' => array(
      'title' => t('Maximum limit'),
      'help' => t("The maximum balance for a user in a currency"),
      'handler' => 'views_handler_field_mcapi_max',
    ),
  );
  return $data;
}
