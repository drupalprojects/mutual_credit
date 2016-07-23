<?php

/**
 * @file
 * Provide views data and handlers for mcapi.module.
 *
 * @ingroup views_module_handlers
 */

use \Drupal\mcapi\Views\WalletViewsData;
use Drupal\mcapi\Mcapi;

/**
 * Implements hook_views_data_alter().
 */
function mcapi_views_data_alter(&$data) {
  foreach (Mcapi::walletableBundles() as $entity_type => $bundles) {
    $table_name = \Drupal::entityTypeManager()->getDefinition($entity_type)->getBaseTable();
    WalletViewsData::addWalletSummaries($data[$table_name]);
  }
}
