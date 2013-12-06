<?php

/**
 * @file
 * Provide views data and handlers for transaction.module.
 *
 * @ingroup views_module_handlers
 */


/**
 * Defines the result for hook_views_data().
 */
function mcapi_views_data() {
  $data['mcapi_transactions']['table'] = array(
    'group'  => t('Transaction'),
    'entity type' => 'mcapi_transaction',
    'base' => array(
      'field' => 'xid',
      'title' => t('Transactions'),
      'help' => t('Records of transactions between users'),
      'access query tag' => 'transaction_views_access',
      'weight' => 5,
      'defaults' => array(
        //'field' => 'serial',//only base field itself works at the moment
        'field' => 'xid',
      )
    ),
  	'wizard_id'=> 'transactions',//this links it to the wizard plugin
  );
  //TODO
  //This isn't working to join the table to all transaction entity queries
  //I've no idea how it is supposed to work, especially as this is a one-to-many relationship
  $data['mcapi_transactions_worths']['table'] = array(
    'entity type' => 'mcapi_transaction',
    'group' => t('Transaction'),
    'join' => array(
      'mcapi_transactions' => array(
        'left_field' => 'xid',
        'field' => 'xid',
        'type' => 'LEFT'//not sure about this
      ),
      'mcapi_transactions_worths' => array(
        'left_field' => 'xid',
        'field' => 'xid',
        'type' => 'LEFT'//not sure about this
      ),
    )
  );

  $data['mcapi_transactions']['serial'] = array(
    'title' => t('Serial'), // The item it appears as on the UI,
    'help' => t('The serial number of the transaction and dependents'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['mcapi_transactions']['xid'] = array(
    'title' => t('Transaction id'), // The item it appears as on the UI,
    'help' => t('The unique database key of the transaction'),
    'field' => array(
      'id' => 'numeric',//this might be 'transaction' if it exists
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mcapi_transactions']['payer'] = array(
    'title' => t('Payer'),
    'help' => t('The user who gave currency in the transaction.'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Payer'),
      'relationship field' => 'payer'
    ),
    'filter' => array(
      'id' => 'user_name',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'trader',
    ),
  );
  $data['mcapi_transactions']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The user who received currency in the transaction.'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Payee'),
      'relationship field' => 'payee'
    ),
    'filter' => array(
      'id' => 'user_name',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'trader',
    ),
  );
  $data['mcapi_transactions']['description'] = array(
    'title' => t('Description'),
    'help' =>  t('A one line description of what was exchanged.'),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'field' => array(
      'id' => 'standard',
    ),
  );

  $data['mcapi_transactions']['state'] = array(
    'title' => t('State'),
    'help' => t('The name of the workflow state of the transaction'),
    'field' => array(
      'id' => 'mcapi_state',
    ),
    'filter' => array(
      'id' => 'mcapi_state',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mcapi_transactions']['type'] = array(
    'title' => t('Type'),
    'help' => t('Which form or module which created the transaction'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'mcapi_type',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['mcapi_transactions']['creator'] = array(
    'title' => t('Creator'),
    'help' => t('The user who first created the transaction.'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
      'label' => t('Creator'),
      'relationship field' => 'creator'
    ),
    'filter' => array(
      'id' => 'user_name',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'trader',
    ),
  );
  $data['mcapi_transactions']['created'] = array(
    'title' => t('Created'),
    'help' => t('The second the transaction was created.'),
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

  //virtual fields
  $data['mcapi_transactions']['operations'] = array(
    'title' => t('Operations'),
    'help' => t('What the user can do to the transaction'),
    'field' => array(
      'id' => 'transaction_operations',
      'additional fields' => array('serial')
    )
  );
  $data['mcapi_transactions']['worths'] = array(
    'title' => t('Amounts'),
    'help' => t('Currency flows per transaction'),
    'field' => array(
      'id' => 'worths',
    )
  );
  //working with raw values directly from the worths table
  $data['mcapi_transactions_worths']['currcode'] = array(
    'title' => t('Currency'),
    'field' => array(
      'help' => t('Show the currency only'),
      'id' => 'currcode',
      'additional fields' => array('currcode')
    ),
    'filter' => array(
      'help' => t('Transactions using specific currencies'),
      'id' => 'currcode',
    ),
    'argument' => array(
      'help' => t('Transactions using specific currencies'),
      'id' => 'string',
    )
  );
  $data['mcapi_transactions_worths']['quantity'] = array(
    'title' => t('Quantity'),
    'help' => t('The raw numerical value of the transaction'),
    'field' => array(
      'id' => 'quantity',
      'additional fields' => array('quantity')
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    )
  );

  $data['mcapi_transactions_index']['table'] = array(
  	'group'  => t('Transaction index'),
  	'entity type' => 'mcapi_transaction',
  	'base' => array(
  		'field' => 'xid',
  	  'title' => t('Transaction index'),
  		'help' => t('Transaction index table'),
  		'access query tag' => 'transaction_views_access',
  		'weight' => 5,
  		'defaults' => array(
    		//'field' => 'serial',//only base field itself works at the moment
  			'field' => 'xid',
  		)
  	),
  	'wizard_id'=> 'transaction_index',//this links it to the wizard plugin
  );

  $data['mcapi_transactions_index']['serial'] = array(
  	'title' => t('Serial'), // The item it appears as on the UI,
  	'help' => t('The serial number of the transaction and dependents'),
  	'field' => array(
  		'id' => 'standard',
  	),
  	'sort' => array(
  		'id' => 'standard',
  	),
  );
  $data['mcapi_transactions_index']['xid'] = array(
  	'title' => t('Transaction id'), // The item it appears as on the UI,
  	'help' => t('The unique database key of the transaction'),
  	'field' => array(
  		'id' => 'numeric',//this might be 'transaction' if it exists
  	),
  	'sort' => array(
  		'id' => 'standard',
  	),
  );
  $data['mcapi_transactions_index']['uid1'] = array(
  	'title' => t('1st trader'),
  	'help' => t('The user we are concerned about'),
  	'relationship' => array(
  		'id' => 'standard',
  		'base' => 'users',
  		'field' => 'uid',
  		'label' => t('1st trader'),
  		'relationship field' => 'uid1'
  	),
  	'filter' => array(
  		'id' => 'user_name',
  	),
  	'argument' => array(
  		'id' => 'numeric',
  	),
  	'field' => array(
  		'id' => 'trader',
  	),
  );
  $data['mcapi_transactions_index']['uid2'] = array(
  	'title' => t('2nd trader'),
  	'help' => t('The user the 1st user traded with'),
  	'relationship' => array(
  		'id' => 'standard',
  		'base' => 'users',
  		'field' => 'uid',
  		'label' => t('2nd trader'),
  		'relationship field' => 'uid2'
  	),
  	'filter' => array(
  		'id' => 'user_name',
  	),
  	'argument' => array(
  		'id' => 'numeric',
  	),
  	'field' => array(
  		'id' => 'trader',
  	),
  );
  $data['mcapi_transactions_index']['description'] = array(
    'title' => t('Description'),
    'help' =>  t('A one line description of what was exchanged.'),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'field' => array(
      'id' => 'standard',
    ),
  );

  $data['mcapi_transactions_index']['state'] = array(
  	'title' => t('State'),
  	'help' => t('The name of the workflow state of the transaction'),
  	'field' => array(
  		'id' => 'mcapi_state',
  	),
  	'filter' => array(
  		'id' => 'mcapi_state',
  	),
  	'sort' => array(
  		'id' => 'standard',
  	),
  );

  $data['mcapi_transactions_index']['type'] = array(
  	'title' => t('Type'),
  	'help' => t('Which form or module which created the transaction'),
  	'field' => array(
  		'id' => 'standard',
  	),
  	'filter' => array(
  		'id' => 'mcapi_type',
  	),
  	'sort' => array(
  		'id' => 'standard',
  	),
  );
  $data['mcapi_transactions_index']['created'] = array(
    'title' => t('Created'),
    'help' => t('The second the transaction was created.'),
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

  //virtual fields
  $data['mcapi_transactions_index']['operations'] = array(
  	'title' => t('Operations'),
  	'help' => t('What the user can do to the transaction'),
  	'field' => array(
  		'id' => 'transaction_operations',
  		'additional fields' => array('serial')
  	)
  );
  $data['mcapi_transactions_index']['worths'] = array(
  	'title' => t('Amounts'),
  	'help' => t('Currency flows per transaction'),
  	'field' => array(
  		'id' => 'worths',
  	)
  );

  return $data;
}

/*
* implements hook_views_data_alter
*/
function mcapi_views_data_alter(&$data) {
  //this is a powerful way for virtual field balances to be accessible to views based on the user table
  //however it does require a query for each row which calls on a special API function
  $data['users']['current_balance'] = array(
    'title' => t('Transaction statistic'),
    'help' => t('The balance, trading volume, or transaction count for each user'),
    'field' => array(
      'id' => 'mcapi_userstat',
    )
  );
}


function transaction_access_callback_perm_transact_views($query, $condition, $state) {
  $condition->condition(1, user_access('transact'));
}
function transaction_access_callback_perm_manage_all_views($query, $condition, $state) {
  $condition->condition(1, user_access('manage all transactions'));
}
function transaction_access_callback_payee_views($query, $condition, $state) {
  $condition->condition('mcapi_transactions.payee', \Drupal::currentUser()->id());
}
function transaction_access_callback_payer_views($query, $condition, $state) {
  $condition->condition('mcapi_transactions.payer', \Drupal::currentUser()->id());
}
function transaction_access_callback_open_views($query, $condition, $state) {
  $condition->condition(1, 1);
}
function transaction_access_callback_creator_views($query, $condition, $state) {
  $condition->condition('mcapi_transactions.creator', \Drupal::currentUser()->id());
}

/*
 * implements hook_query_TAG_alter for views on the mcapi_transactions table
 * this modifies the query only to show the transactions visible to the user
 * according to currency view access settings per transaction state
 * this is pretty elaborate, but the access control has to be done in the query
 * or views pager won't know how many results there were
 */
function __mcapi_query_transaction_views_access_alter(QueryAlterableInterface $query) {
  $query->addJoin('LEFT', 'field_data_worth', 'w', 'w.entity_id = mcapi_transactions.xid');return;
  $states = array_keys(mcapi_get_states());
  //what currencies can the current user see?
  $currencies = currencies_load(\Drupal::currentUser());
  $or_currcodes = db_or();//where ((currcode = A and (blah)) OR ((currcode = B and (blah))
  foreach ($currencies as $currcode => $currency) {
    //where (currcode = A and (state = x) OR (state = Y) )
    $state_or_state = db_or();//where state = A OR B
    foreach ($states as $state) {
      $callbacks = array_filter($currency->view_transaction_states[$state]);
      foreach ($callbacks as $callback) {
        $callback .= '_views';
        $state_and_callback = db_and()->condition('mcapi_transactions.state', $state);
        $callback($query, $state_and_callback, $state);
        $state_or_state->condition($state_and_callback);
      }
    }
    $currcode_and_states = db_and()->condition('w.worth_currcode', $currcode)->condition($state_or_state);
    $or_currcodes->condition($currcode_and_states);
  }
  //to see this working, show the sql query on admin/structure/views/settings/basic
  $query->condition($or_currcodes);
}


/*
 * implements hook_form_FORM_ID_alter
 * renames the exposed filters on views forms
 */
function mcapi_form_views_exposed_form_alter(&$form, $form_state) {
  $description = t('Comma separated usernames');
  $form['payee']['#description'] = $description;
  $form['payer']['#description'] = $description;
}


/*
 * implements hook_views_pre_render
 * converts zero values display in transaction views
 */
function mcapi_views_pre_render(&$view) {
  if (in_array($view->base_table, array('mcapi_transactions', 'mcapi_index_views')))  {
    module_load_include('inc', 'mcapi');
    foreach($view->result as $rowid => $row) {
      if (!property_exists($row, 'field_worth')) return;
      foreach ($row->field_worth as $delta => $item) {
        mcapi_worth_item_zero_adjustment($view->result[$rowid]->field_worth[$delta]['rendered']);
      }
    }
  }
}
