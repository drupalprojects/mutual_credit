<?php

use Drupal\mcapi\Entity\Exchange;

/**
 * implements hook_views_data_alter
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
  foreach (Exchange::getEntityFieldnames() as $entity_type => $fieldname) {
    if (!$fieldname) continue;
    $argument = &$data[$entity_type .'__'. $fieldname][$fieldname .'_target_id']['argument'];
    $argument['help'] = t("Show only entities according to the exchange they are in");
    $argument['id'] = 'standard';//TODO this might need customisig
    $argument['additional fields'] = array();//no idea how these were helping
    unset($data[$entity_type .'__'. $fieldname]['delta']);
    unset($data[$entity_type .'__'. $fieldname][$fieldname .'_revision_id']);
  }

}
