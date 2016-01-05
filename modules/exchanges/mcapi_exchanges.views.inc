<?php

use Drupal\mcapi\Mcapi;


/**
 * implements hook_views_data_alter
 * @todo alter this when exchange is fieldAPI again.
*/
function __mcapi_exchanges_views_data_alter(&$data) {
  //make an argument for every entity that uses the field 'exchanges'
  foreach (Mcapi::walletableBundles() as $entity_type => $bundles) {
    $argument = &$data[$entity_type .'__'. EXCHANGE_OG_FIELD][EXCHANGE_OG_FIELD .'_target_id']['argument'];
    $argument['help'] = t("Show only entities according to the exchange they are in");
    $argument['id'] = 'standard';//@todo this might need customisig
    $argument['additional fields'] = [];//no idea how these were helping
    unset($data[$entity_type .'__'. EXCHANGE_OG_FIELD]['delta']);
    unset($data[$entity_type .'__'. EXCHANGE_OG_FIELD][EXCHANGE_OG_FIELD .'_revision_id']);
  }

}
