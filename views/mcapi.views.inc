<?php
// $Id:

/*
 * Implements views hook_views_data
 */
function mcapi_views_data() {
  $data = array();
  $data['mcapi_transactions']['table']['group']  = t('Transaction');
  $data['mcapi_transactions']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
    'field' => 'xid',
    'title' => t('Transactions'),
    'help' => t('Records of transactions between users'),
    'access query tag' => 'transaction_access_views',
    'defaults' => array(
      'field' => 'xid',
    ),
  );
  $data['mcapi_transactions']['xid'] = array(
    'title' => t('Transaction ID'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The unique ID of the transaction.'),
      'handler' => 'views_handler_field',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    )
  );
  $data['mcapi_transactions']['payer'] = array(
    'title' => t('Payer'),
    'help' => t('The user who gave currency in the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Payer'),
    ),
  );
  $data['mcapi_transactions']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The user who gave currency in the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Payee'),
    ),
  );
  $data['mcapi_transactions']['state'] = array(
    'title' => t('State'),
    'help' => t("Transactions move between states, until they are 'finished'."),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_state',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_states',
    ),
  );

  $data['mcapi_transactions']['type'] = array(
    'title' => t('Type'),
    'help' => t('Which form or module which created the transaction'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_types',
    ),
  );
  $data['mcapi_transactions']['creator'] = array(
    'title' => t('Creator'),
    'help' => t('The user who first created the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Creator'),
    ),
  );
  $data['mcapi_transactions']['created'] = array(
    'title' => t('created'),
    'help' => t("the second the transaction was created"),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_mcapi_dates_various',
    )
  );
  $data['mcapi_transactions']['modifer'] = array(
    'title' => t('Modifier'),
    'help' => t('The user who last touched the transaction.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'label' => t('Modifier'),
    ),
  );
  $data['mcapi_transactions']['modified'] = array(
    'title' => t('Corrected'),
    'help' => t("the second the transaction was corrected"),
    'field' => array(
      'handler' => 'views_handler_field_time_interval',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );
  $data['mcapi_transactions']['view_transaction'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Link to the transaction certificate itself.'),
      'handler' => 'views_handler_field_transaction_link',
    ),
  );

  $data['mcapi_transactions']['edit_transaction'] = array(
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the transaction.'),
      'handler' => 'views_handler_field_transaction_link_edit',
    ),
  );

  $data['mcapi_transactions']['links'] = array(
    'title' => t('Admin links'),
    'help' => t("Edit and delete links"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_links',
    )
  );


  //this is a powerful way for virtual field balances to be accessible to views based on the user table
  //however it does require a query for each row which calls on a special API function
  //so writing more of these might not be the best way
  //also filter and sort handlers won't work on a virtual field
  $data['users']['current_balance'] = array(
    'title' => t('Current balance'),
    'field' => array(
      'help' => t("The balance for each user"),
      'handler' => 'views_handler_field_mcapi_users_balance',
      'click sortable' => TRUE
    )
  );
  return $data;
}

/**
 * Implements hook_views_plugins().
 * can't get this working
 */
function mcapi_views_plugins() {
  return array(
    'module' => 'views', // This just tells our themes are elsewhere.
    'row' => array(
      'transaction' => array(
        'title' => t('Transaction'),
        'help' => t('The spacious, decorated, transaction certificate'),
        'handler' => 'views_plugin_row_transaction',
        'base' => array('mcapi_transactions'),//plugin will only be available when this is the base table
        'uses options' => TRUE,
        'type' => 'normal',
      )
    )
  );
}

