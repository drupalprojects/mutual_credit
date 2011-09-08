<?php
//Id;

/*
 * Implements views hook_views_data
 */
function mcapi_views_views_data() {

  $data['mcapi_index']['table']['group'] = t('Transaction index');
  $data['mcapi_index']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
    'field' => 'uid',
    'title' => t('Transaction totals'),
    'help' => t('Balances of users.'),
    'defaults' => array('xid')
  );

  $data['mcapi_index']['table']['join'] = array(
    //doesn't do anything that I can see, instead using relationship which must be added manually
    'mcapi_transactions' => array(
      'left_field' => 'xid',
      'field' => 'xid',
    ),
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid1',
    ),
    'mcapi_currencies' => array(
      'left_field' => 'currcode',
      'field' => 'currcode'
    )
  );

  $data['mcapi_index']['xid'] = array(
    'title' => t('Transactions'), // The item it appears as on the UI,
    'relationship' => array(
      'help' => t('Shows more transaction fields'),
      'handler' => 'views_handler_relationship',
      'base' => 'mcapi_transactions',
      'base field' => 'xid',
      'field' => 'xid',
      'label' => t('transactions'),
    )
  );
  $data['mcapi_index']['delta'] = array(
    'title' => t('delta'), // The item it appears as on the UI,
    'filter' => array(
      'help' => t('Choose 0 to select every transaction once'),
      'handler' => 'views_handler_filter_boolean_operator'
    ),
  );
  $data['mcapi_index']['uid1'] = array(
    'title' => t('1st user'), // The item it appears as on the UI,
    'help' => t('The the transactor, whose perspective we are looking from'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only transactions where the specified user paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    )
  );
  $data['mcapi_index']['uid2'] = array(
    'title' => t('2nd Person'),
    'help' => t("The transactee"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only transactions where the specified user paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_index']['currcode'] = array(
    'title' => t('Currency'),
    'help' => t("The currency ID"),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
    'filter' => array(
       'handler' => 'views_handler_filter_mcapi_currency'
    ),
  );
  $data['mcapi_index']['income'] = array(
    'title' => t('Income'), // The item it appears as on the UI,
    'help' => t('Number of units of currency incoming'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_quantity_index',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('transactions above or below a certain value'),
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_index']['expenditure'] = array(
    'title' => t('Expenditure'), // The item it appears as on the UI,
    'help' => t('Number of units of currency outgoing'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_quantity_index',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('transactions above or below a certain value'),
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_index']['diff'] = array(
    'title' => t('value'), // The item it appears as on the UI,
    'help' => t("Change in the users balance"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_quantity_index',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_index']['volume'] = array(
    'title' => t('Volume'), // The item it appears as on the UI,
    'help' => t("Absolute value of the trade"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_quantity_index',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_index']['balance'] = array(
    'title' => t('Balance'), // The item it appears as on the UI,
    'help' => t("Running balance after each transaction") . ' (fragile field at the moment)',
    'field' => array(
      'handler' => 'views_handler_field_mcapi_quantity_index',
    ),
  );
  $data['mcapi_index']['created'] = array(
    'title' => t('Created moment'),
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
  );
  
  return $data;
}

function mcapi_views_views_data_alter(&$items) {
  $items['mcapi_transactions']['table']['join']['mcapi_index'] = array(
    'left_field' => 'xid',
    'field' => 'xid'
  );

  $items['mcapi_currencies']['table']['join']['mcapi_index'] = array(
    'left_field' => 'currcode',
    'field' => 'currcode',
  );

  //not doing anything
  $items['mcapi_transactions']['xid']['relationship'] = array(
    'help' => t('Shows more transaction fields'),
    'handler' => 'views_handler_relationship',
    'base' => 'mcapi_index',
    'base field' => 'xid',
    'field' => 'xid',
    'label' => t('transactions'),
  );

  /*
  $items['users']['table']['join']['mcapi_index'] = array(
    'left_field' => 'uid1',
    'field' => 'uid',
  );
  $items['users']['table']['join']['mcapi_index'] = array(
    'left_field' => 'uid1',
    'field' => 'uid',
  );
   */
}