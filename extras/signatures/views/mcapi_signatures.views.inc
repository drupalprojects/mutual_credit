<?php

/*
 * Implements views hook_views_data
 */
function mcapi_signatures_views_data() {

  $data['mcapi_signatures']['table']['group']  = t('Signatures');
  $data['mcapi_signatures']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
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

  $data['mcapi_signatures']['serial'] = array(
    'title' => t('Signatures'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'mcapi_transactions',
      'field' => 'serial',
      'label' => t('Transaction'),
    )
  );
  $data['mcapi_signatures']['uid'] = array(
    'title' => t('Current signatory'),
    'filter' => array(
      'help' => t('Show only transactions on which the current user is a signatory'),
      'handler' => 'views_handler_filter_user_current'
    ),
    'argument' => array(
      'help' => t('Show only transactions on which the passed user is a signatory'),
      'handler' => 'views_handler_argument_user_uid',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Signatory'),
    ),
  );
  $data['mcapi_signatures']['pending'] = array(
    'title' => t('Is pending'),
    'filter' => array(
      'help' => t('The signature is still pending'),
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
      'help' => t('The signature is still pending'),
      'handler' => 'views_handler_filter_sort',
    )
  );
  //virtual field
  $data['mcapi_signatures']['sign'] = array(
    'title' => t('Sign here'),
    'field' => array(
      'help' => t('Link to sign the transactions'),
      'handler' => 'views_handler_field_mcapi_sign'
    )
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
  $data['mcapi_transactions']['sign'] = array(
    'title' => t('Sign'),
    'field' => array(
      'help' => t('Sign, if permitted'),
      'handler' => 'views_handler_field_transaction',
      'link_title' => t('Sign')
    )
  );
  $data['mcapi_transactions']['signoff'] = array(
    'title' => t('Sign off'),
    'field' => array(
      'help' => t('Sign on behalf of everyone, if permitted'),
      'handler' => 'views_handler_field_transaction',
      'link_title' => t('Sign off')
    )
  );
}