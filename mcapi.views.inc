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

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Renames the exposed filters on views forms.
 */
function mcapi_form_views_exposed_form_alter(&$form, $form_state) {
  $description = t('Comma separated usernames');
  $form['payee']['#description'] = $description;
  $form['payer']['#description'] = $description;
}
