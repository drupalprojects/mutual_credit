<?php

use Drupal\mcapi\Exchanges;

/**
 * implements hook_views_data_alter
 * @todo alter this when exchange is fieldAPI again.
*/
function mcapi_exchanges_views_data_alter(&$data) {
  $data['mcapi_transaction']['exchange'] = array(
    'title' => t('Exchange'),
    'help' => t('Exchange governing the transaction.'),
    'field' => array(
      'id' => 'standard'
    ),
    'filter' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    )
  );
  //TODO ensure this field is present.
  $data['mcapi_transactions_index']['exchange'] = array(
    'title' => t('Exchange'),
    'help' => t('Exchange governing the transaction.'),
    'field' => array(
      'id' => 'standard'
    ),
    'filter' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    )
  );

  //make an argument for every entity that uses the field 'exchanges'
  foreach (Exchange::walletableBundles() as $entity_type => $bundles) {
    $argument = &$data[$entity_type .'__'. EXCHANGE_OG_REF][EXCHANGE_OG_REF .'_target_id']['argument'];
    $argument['help'] = t("Show only entities according to the exchange they are in");
    $argument['id'] = 'standard';//TODO this might need customisig
    $argument['additional fields'] = [];//no idea how these were helping
    unset($data[$entity_type .'__'. EXCHANGE_OG_REF]['delta']);
    unset($data[$entity_type .'__'. EXCHANGE_OG_REF][EXCHANGE_OG_REF .'_revision_id']);
  }

}
