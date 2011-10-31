<?php
//Id;

/*
 * Implements views hook_views_data
 */
function mcapi_views_views_data() {
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
    'title' => t('Payer'), // The item it appears as on the UI,
    'help' => t('The user who gave currency in the transaction.'),
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
  $data['mcapi_transactions']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The user who received currency in the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only transactions where the specified user was paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
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
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
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
      'handler' => 'views_handler_argument_mcapi_created_year_month',
    )
  );
  $data['mcapi_transactions']['modifer'] = array(
    'title' => t('Modifier'),
    'help' => t('The user who last touched the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
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

  $data['mcapi_transactions']['participant'] = array(
    'argument' => array(
      'title' => t('Involved user'),
      'help' => t('Show only those exchanges in which a user was involved'),
      'handler' => 'views_handler_argument_mcapi_participant',
    ),
    'field' => array(
      'title' => t('Other user'),
      'help' => t("Show the other user who wasn't passed in the views argument"),
      'handler' => 'views_handler_field_mcapi_participant',
      'click sortable' => TRUE
    )
  );
  $data['mcapi_transactions']['balance'] = array(
    'help' => t("Sum of all transactions since started. Requires the 'participant' argument"),
    'field' => array(
      'title' => t('Running balance'),
      'handler' => 'views_handler_field_mcapi_balance',
      'click sortable' => TRUE
    ),
    'sort' => array(
      'title' => t('Running balance'),
      'handler' => 'views_handler_sort_mcapi_balance',
    )
  );

  $data['mcapi_transactions']['view_transaction'] = array(
    'field' => array(
      'title' => t('Link'),
      'help' => t('Provide a simple link to the transaction certificate.'),
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

  /*
   * now for the transaction index table
   */
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
    'help' => t("Indexed running balance after each transaction") . ' (fragile field at the moment)',
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


/*
 * implements views hook_views_data_alter
 */
function mcapi_views_data_alter(&$items) {
  //not sure if this does anything
  $items['users']['table']['join']['mcapi_transactions'] = array(
    'left_table' => 'mcapi_transactions',
    'left_field' => 'creator',
    'field' => 'uid',
  );
}


//shorten the user select text boxes on the exposed views form
//this could also be done by css but I'm not creating a file just for that
function mcapi_form_views_exposed_form_alter(&$form, $form_state) {
  $form['payee']['#size'] = 30;
  $form['payer']['#size'] = 30;
}
