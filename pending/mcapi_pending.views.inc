<?php

/*
 * Implements views hook_views_data
 */
function mcapi_pending_views_data() {

  $data['mcapi_signatures']['table']['group']  = t('Signatures');
  $data['mcapi_signatures']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
    'field' => 'xid',
    'title' => t('Signatures'),
    'help' => t('Signatures needed on pending transactions'),
    'access query tag' => 'transaction_access_views',
    'defaults' => array(
      'field' => 'xid',
    ),
  );
  $data['mcapi_signatures']['table']['join'] = array(
    'mcapi_transactions' => array(
      'left_field' => 'xid',
      'field' => 'xid',
      'type' => 'LEFT',
    ),
  );

  $data['mcapi_signatures']['xid'] = array(
    'title' => t('Signatures'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'mcapi_transactions',
      'field' => 'xid',
      'label' => t('Transaction'),
    )
  );
  $data['mcapi_signatures']['uid'] = array(
    'title' => t('Signatory'),
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
    'title' => t('Signature is pending'),
    'filter' => array(
      'help' => t('If the user has permission'),
      'handler' => 'views_handler_filter_boolean_operator',
    )
  );
  $data['mcapi_signatures']['sign'] = array(
    'title' => t('Sign here link'),
    'field' => array(
      'help' => t('Link for current user to sign, or blank'),
      'handler' => 'views_handler_field_mcapi_sign',
    )
  );

  return $data;
}