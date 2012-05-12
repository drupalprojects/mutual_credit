<?php
//Id;

/*
 * Implements views hook_views_data
 */
function mcapi_index_views_views_data() {
  $data = array();
  $data['mcapi_index']['table']['group'] = t('Transaction index');
  $data['mcapi_index']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
    'field' => 'serial',
    'title' => t('Transaction totals'),
    'help' => t('Transaction totals'),
    'access query tag' => 'transaction_access_views',
    'defaults' => array('diff')
  );

  $data['mcapi_index']['uid1'] = array(
    'title' => t('1st user'), // The item it appears as on the UI,
    'help' => t('The the transactor, whose perspective we are looking from'),
    'field' => array(
      'handler' => 'views_handler_field',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'click sortable' => TRUE,
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid1',
      'label' => t('1st user'),
    ),
  );
  $data['mcapi_index']['uid2'] = array(
    'title' => t('2nd user'),
    'help' => t("The transactee"),
    'field' => array(
      'handler' => 'views_handler_field',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'click sortable' => TRUE,
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'field' => 'uid2',
      'label' => t('2nd user'),
    ),
  );
  $data['mcapi_index']['income'] = array(
    'title' => t('Income'), // The item it appears as on the UI,
    'help' => t('Number of units of currency incoming'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_index_quantity',
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
      'handler' => 'views_handler_field_mcapi_index_quantity',
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
    'title' => t('Value'), // The item it appears as on the UI,
    'help' => t("Change in the users balance"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_index_quantity',
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
      'handler' => 'views_handler_field_mcapi_index_quantity',
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
    'help' => t("Indexed running balance after each transaction. (1 database query per row)"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_index_balance',
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
    'argument' => array(
      'handler' => 'views_handler_argument_mcapi_dates_various'
    )
  );

  $data['mcapi_index']['currcode'] = array(
    'title' => t('Currency'),
    'help' => t("The currency ID"),
    'field' => array(
      //this needs a custom handler
      'handler' => 'views_handler_field_mcapi_index_currcode',
      'click sortable' => TRUE,
    ),
    'filter' => array(
       'handler' => 'views_handler_filter_mcapi_currency'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    ),
  );
  return $data;
}

/*
 * implements hook_views_data_alter
 * anything (field) that is joined to mcapi_transactions should be joined to mcapi_index also
 * except worth field, which is copied in the table already
 */
function mcapi_index_views_views_data_alter(&$data) {
  foreach ($data as $handler => $info) {
    //ignore the fields we konw about, this is just intended for fields added via field API
    if (in_array($handler, array('field_data_worth', 'mcapi_signatures'))) continue;
    if (isset($info['table']['join']['mcapi_transactions'])) {
      $data[$handler]['table']['join']['mcapi_index'] = $info['table']['join']['mcapi_transactions'];
      $fieldname = $info['entity_id']['field']['moved to'][1];
      if (is_array($info[$fieldname]['field']['entity_tables'])) {
        $data[$handler][$fieldname]['field']['entity_tables']['mcapi_index'] = 'transaction';
      }
    }
  }
}


/**
 * Implements hook_views_plugins().
 * can't get this working
 */
function mcapi_index_views_views_plugins() {
  return array(
    'module' => 'views', // This just tells our themes are elsewhere.
    'access' => array(
      'arg1user' => array(
        'title' => t('Arg 1 user'),
        'help' => t('Access will be granted to only if the user is in arg 1'),
        'handler' => 'views_plugin_access_user_arg1',
        'uses options' => FALSE,
      ),
    )
  );
}
