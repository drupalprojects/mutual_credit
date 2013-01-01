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
    'access query tag' => 'transaction_view_access_views',
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
    'title' => t('Transactions with signatures'),
    'help' => t('The serial number of the transaction'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'mcapi_transactions',
      'field' => 'serial',
      'label' => t('Transaction'),
    )
  );
  //some of these are a bit confused - need more time!
  $data['mcapi_signatures']['uid'] = array(
    'title' => t('Signatory'),
    'description' => t("The user's signature on a transaction"),
    'field' => array(
      'help' => t('Signatory to a transaction'),
      'handler' => 'views_handler_field_user'
    ),
    'filter' => array(
      'title' => t('Current user is a signatory'),
      'help' => t('Show only transactions on which the current user is a signatory'),
      'handler' => 'views_handler_filter_user_current'
    ),
    'argument' => array(
      'title' => t('Passed user is a signatory'),
      'handler' => 'views_handler_argument_user_uid',
    ),
    //for some reason this isn't showing a widget so that fields can connect to it
    'relationship' => array(
      'label' => t('Signatory user'),
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid',
    ),
  );
  $data['mcapi_signatures']['pending'] = array(
    'title' => t('Signature needed'),
    'help' => t('The signature is still needed'),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
    ),
    'sort' => array(
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