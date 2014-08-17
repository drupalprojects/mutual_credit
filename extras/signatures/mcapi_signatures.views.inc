<?php

/*
 * Implements views hook_views_data
 */
function mcapi_signatures_views_data() {

  //TODO in alpha12, judging by node revisions, I don't see a way for secondary table to conjure up entities, except as a field
  $data['mcapi_signatures']['table'] = array(
    'group'  => t('Signatures'),
    //'entity type' => 'mcapi_transaction',//This adds xid to the field list for the current table, so we don't want it
    'access query tag' => 'mcapi_views_access',
    'base' => array(
      'field' => 'serial',
      'title' => t('Signatures'),
      'help' => t('Signatures needed on pending transactions'),
      'defaults' => array(
        'field' => 'serial',
      ),
    ),
    'join' => array(
      'mcapi_transaction' => array(
        'left_field' => 'serial',
        'field' => 'serial',
      ),
    )
  );

  $data['mcapi_signatures']['serial'] = array(
    'title' => t('Serial'),
    'help' => t("The serial number of the transaction"),
    'field' => array(
      'id' => 'standard'
    ),
    'argument' => array(
      'id' => 'standard',
    )
  );

  //some of these are a bit confused - need more time!
  $data['mcapi_signatures']['uid'] = array(
    'title' => t('Signatory'),
    'help' => t("The user whose signature is required"),
    'field' => array(
      'id' => 'standard'
    ),
    'filter' => array(
      'title' => t('User is /is not a signatory'),
      'help' => t('transactions on which the current user is / is not a signatory'),
      'id' => 'user_current'
    ),
    'argument' => array(
      'title' => t('User is /is not a signatory'),
      'id' => 'user_uid',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Signatory'),
      'relationship field' => 'uid'
    ),
  );
  $data['mcapi_signatures']['signed'] = array(
    'title' => t('Date of signature'),
    'help' => t('The date the transaction was signed'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  //TODO this is temp I hope until we can reference the transaction entity sentence display mode via the view style
  $data['mcapi_signatures']['entity'] = array(
    'title' => t('Transaction sentence (workaround)'),
    'help' => t('Virtual field to render a transaction sentence from the serial number'),
    'field' => array(
      'id' => 'sentence',
    )
  );
  return $data;
}


function mcapi_signatures_views_data_alter(&$data) {
  // For other base tables, explain how we join
  $data['mcapi_transaction']['table']['join']['mcapi_signatures'] = array(
    'left_table' => 'mcapi_signatures',
    'left_field' => 'serial',
    'field' => 'serial',
  );
}
