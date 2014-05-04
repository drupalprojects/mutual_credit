<?php

/*
 * Implements views hook_views_data
 */
function mcapi_signatures_views_data() {

  $data['mcapi_signatures']['table']['group']  = t('Signatures');
  $data['mcapi_signatures']['table']['base'] = array(
    'field' => 'serial',
    'title' => t('Signatures'),
    'help' => t('Signatures needed on pending transactions'),
    'access query tag' => 'transaction_access_views',
    'defaults' => array(
      'field' => 'serial',
    ),
  );
  $data['mcapi_signatures']['table']['join'] = array(
    'mcapi_transactions' => array(
      'left_field' => 'serial',
      'field' => 'serial',
      'type' => 'LEFT',
    ),
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
  return $data;
}


function mcapi_signatures_views_data_alter(&$data) {
  // For other base tables, explain how we join
  $data['mcapi_transactions']['table']['join']['mcapi_signatures'] = array(
    'left_table' => 'mcapi_signatures',
    'left_field' => 'serial',
    'field' => 'serial',
  );
}
