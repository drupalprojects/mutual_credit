<?php

/**
 * @file
 */

use Drupal\mcapi\Mcapi;
/**
 * Implements hook_views_data_alter.
 *
 * @todo alter this when exchange is fieldAPI again.
 */
function mcapi_exchanges_views_data_alter(&$data) {
  foreach (Mcapi::walletableBundles() as $entity_type_id => $bundles) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $table = $entity_type->getDataTable() ? : $entity_type->getBaseTable();
    $key = $entity_type->getKey('id');
    foreach (array_keys($bundles) as $bundle) {
      $data[$table]['first_wallet'] = array(
        'relationship' => array(
          'title' => t('Wallet'),
          'label'  => t('Wallet'),
          'help' => t('Shows ONLY the first wallet held by each @type', ['@type' => $entity_type->getLabel()]),
          'id' => 'standard',
          'relationship field' => $key,
          'outer field' => "{$table}.{$key}",
          'argument table' => $table,
          'argument field' => $key,
          'base' => 'mcapi_wallet',
          'field' => 'holder_entity_id',
          'base field' => 'holder_entity_id',
          'relationship' => "{$table}:{$key}",
          'extra' => [
             [
               'field' => 'holder_entity_type',
               'value' => $entity_type_id,
             ],
          ],
        ),
      );
      // don't repeat for multiple bundles on the same entityType.
      break;
    }
  }
  $data['group_content_field_data']['exchange_of_user']['argument'] = [
    'title' => t('Exchange of user'),
    'label' => t('Exchange of user'),
    'help' => t('Take a user id and filter by the exchange of that user'),
    'id' => 'user_exchange',
    'field' => 'gid',
  ];

}
