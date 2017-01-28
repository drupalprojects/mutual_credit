<?php

/**
 * @file
 * Views hooks.
 */

/**
 * Implements hook_views_data().
 */
function mcapi_signatures_views_data() {
  // @todo In alpha12, judging by node revisions, I don't see a way for
  // secondary table to conjure up entities, except as a field
  $data['mcapi_signatures']['table'] = [
    'group'  => t('Signatures'),
    // This adds xid to the field list for the current table, so not needed.
    'entity type not used' => 'mcapi_transaction',
    'access query tag' => 'mcapi_views_access',
    'base' => [
      'field' => 'serial',
      'title' => t('Signatures'),
      'help' => t('Signatures needed on pending transactions'),
      'defaults' => [
        'field' => 'serial',
      ],
    ],
    'join' => [
      'mcapi_transaction' => [
        'left_field' => 'serial',
        'field' => 'serial',
      ],
    ],
  ];

  $data['mcapi_signatures']['serial'] = [
    'title' => t('Serial'),
    'help' => t("The serial number of the transaction"),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'standard',
    ],
    'relationship' => [
      'id' => 'standard',
      'base' => 'mcapi_transaction',
      'base field' => 'serial',
      'field' => 'serial',
      'label' => t('Transactions to sign'),
      //'relationship field' => 'serial',
    ],
  ];

  $data['mcapi_signatures']['uid'] = [
    'title' => t('Signatory'),
    'help' => t("The user whose signature is required"),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'title' => t('User is a signatory'),
      'help' => t('transactions on which the CURRENT user is / is not a signatory'),
      'id' => 'user_current',
    ],
    'argument' => [
      'title' => t('User is a signatory'),
      'id' => 'user_uid',
    ],
    'relationship' => [
      'id' => 'standard',
      'base' => 'users',
      'base field' => 'uid',
      'label' => t('Signatory'),
      //'relationship field' => 'uid',
    ],
  ];
  $data['mcapi_signatures']['signed'] = [
    'title' => t('Date of signature'),
    'help' => t('The date the transaction was signed'),
    'field' => [
      'id' => 'signed_date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];
  return $data;
}

/**
 * Implements hook_views_data().
 *
 * @deprecated
 */
function mcapi_signatures_views_data_alter(&$data) {

  return;
  $data['mcapi_transaction']['table']['join']['mcapi_signatures'] = [
    'left_table' => 'mcapi_signatures',
    'left_field' => 'serial',
    'field' => 'serial',
  ];

}
