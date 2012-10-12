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
    'access query tag' => 'transaction_access_views_index',
    'defaults' => array('diff')
  );

  $data['mcapi_index']['xid'] = array(
    'title' => t('Transaction ID'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The Unique database key of the transaction'),
      'handler' => 'views_handler_field',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['mcapi_index']['serial'] = array(
    'title' => t('Unique serial number'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The serial number of the transaction and dependents'),
      'handler' => 'views_handler_field',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['mcapi_index']['uid1'] = array(
    'title' => t('1st user'), // The item it appears as on the UI,
    'help' => t('The transactor, whose perspective we are looking from'),
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
      'handler' => 'views_handler_argument_mcapi_date',
      'help' => t('Limit the date range'),
      'fieldname' => 'created'
    ),
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
  $data['mcapi_index']['links'] = array(
    'title' => t('Transaction links'),
    'help' => t("View and undo links"),
    'field' => array(
      'handler' => 'views_handler_field_transaction_link',
    )
  );
  return $data;
}

/*
 * implements hook_views_data_alter
 * anything (field) that is joined to mcapi_transactions should be joined to mcapi_index also
 * except worth field, which is copied in the table already
 */
function mcapi_index_views_views_data_alter(&$tables) {

  foreach ($tables as $table => $info) {
    //ignore the fields we know about, this is just intended for fields added via field API
    if (in_array($table, array('field_data_worth', 'mcapi_signatures'))) continue;
    if (isset($info['table']['join']['mcapi_transactions'])) {
      $tables[$table]['table']['join']['mcapi_index'] = $info['table']['join']['mcapi_transactions'];
      $fieldname = $info['entity_id']['field']['moved to'][1];
      if (is_array($info[$fieldname]['field']['entity_tables'])) {
        $tables[$table][$fieldname]['field']['entity_tables']['mcapi_index'] = 'transaction';
      }
    }
  }
  //tell the date_views module about this table
  $tables['mcapi_index']['date_argument'] = array(
    'group' => t('Date'),
    'title' => t('Date (!base_table)', array('!base_table' => 'mcapi_index')),
    'help' => t('Filter any Views !base_table date field by a date argument, using any common ISO date/period format (i.e. YYYY, YYYY-MM, YYYY-MM-DD, YYYY-W99, YYYY-MM-DD--P3M, P90D, etc). ', array('!base_table' => 'mcapi_index')),
    'argument' => array(
      'handler' => 'date_views_argument_handler',
      'empty field name' => t('Undated'),
      'is date' => TRUE,
    ),
  );
  // The flexible date filter.
  $tables['mcapi_index']['date_filter'] = array(
    'group' => t('Date'),
    'title' => t('Date (!base_table)', array('!base_table' => 'mcapi_index')),
    'help' => t('Filter any Views !base_table date field.', array('!base_table' => 'mcapi_index')),
    'filter' => array(
      'handler' => 'date_views_filter_handler',
      'empty field name' => t('Undated'),
      'is date' => TRUE,
    ),
  );
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


/*
 * implements hook_query_TAG_alter
 * The transaction_index table doesn't care about states,
 * but we do have to add an access check concerning aggregated data for each currency
 */
function mcapi_query_transaction_access_views_index_alter(QueryAlterableInterface $query) {
  $currencies = &drupal_static('currencies');
  $or_currcodes = db_or();//where currcode = A OR currcode = B
  foreach ($currencies as $currcode => $currency) {
    if ($currency->currency_access['user_aggregated_view']) {
      $or_currcodes->condition('currcode', $currcode);
    }
  }
  //to see this working, show the sql query on admin/structure/views/settings/basic
  $query->condition($or_currcodes);
}