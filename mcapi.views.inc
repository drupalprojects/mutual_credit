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
    //the relationship is now to the wallet table, not the user table
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
      'id' => 'user_uid',
    ),
    'field' => array(
      'id' => 'wallet',
    ),
  );
  $data['mcapi_transactions']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The user who received currency in the transaction.'),
    //the relationship is now to the wallet table, not the user table
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
      'id' => 'user_uid',
    ),
    'field' => array(
      'id' => 'wallet',
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
      'id' => 'mcapi_description',
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
      'id' => 'user_uid',
    ),
    'field' => array(
      'id' => 'standard',
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
  $data['mcapi_transactions']['parent'] = array(
    'title' => t('Parent'),
    'help' => t('Whether the transactioin has a parent.'),
    'field' => array(
      'id' => 'boolean',
    ),
    'filter' => array(
      'id' => 'boolean',
    ),
  );

  //virtual fields
  $data['mcapi_transactions']['operations'] = array(
    'title' => t('Operations'),
    'help' => t('What the user can do to the transaction'),
    'field' => array(
      'id' => 'transaction_operations',
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
  $data['mcapi_transactions_worths']['value'] = array(
    'title' => t('Quantity'),
    'help' => t('The raw numerical value of the transaction'),
    'field' => array(
      'id' => 'numeric',
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
  $data['mcapi_transactions_index']['wallet_id'] = array(
  	'title' => t('Wallet ID'),
  	'help' => t('The wallet we are looking at'),
    //do we need this relationship???
  	'relationship' => array(
  		'id' => 'standard',
  		'base' => 'users',
  		'field' => 'uid',
  		'label' => t('1st party'),
  		'relationship field' => 'wallet_id'
  	),
  	'filter' => array(
  		'id' => 'user_name',
  	),
  	'argument' => array(
  		'id' => 'user_uid',
  	),
  );
  $data['mcapi_transactions_index']['partner_id'] = array(
  	'title' => t('Partner wallet ID'),
  	'help' => t('The user the 1st user traded with'),
  	'relationship' => array(
  		'id' => 'standard',
  		'base' => 'users',
  		'field' => 'uid',
  		'label' => t('2nd party'),
  		'relationship field' => 'partner_id'
  	),
  	'filter' => array(
  		'id' => 'user_name',
  	),
  	'argument' => array(
  		'id' => 'user_uid',
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
  $data['mcapi_transactions_index']['incoming'] = array(
    'title' => t('Income'),
    'help' => t('The income from the transaction; positive or zero'),
    'field' => array(
      'id' => 'worth',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );
  $data['mcapi_transactions_index']['outgoing'] = array(
    'title' => t('Expenditure'),
    'help' => t('The outgoing quantity of the transaction; positive or zero'),
    'field' => array(
      'id' => 'worth',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );
  $data['mcapi_transactions_index']['diff'] = array(
    'title' => t('Change in balance'),
    'help' => t('The difference to the balance; positive or negative'),
    'field' => array(
      'id' => 'worth',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );
  $data['mcapi_transactions_index']['volume'] = array(
    'title' => t('Transaction volume'),
    'help' => t('The quantity of the transaction; always positive'),
    'field' => array(
      'id' => 'worth',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  //virtual fields
  $data['mcapi_transactions_index']['balance'] = array(
    'title' => t('Running balance'),
    'help' => t("The sum of all the user's previous transactions."),
    'field' => array(
      'id' => 'balance',
  		'additional fields' => array(
  		  'currcode' => array('table' => 'mcapi_transactions_index', 'field' => 'currcode')
  		)
    ),
  );
  $data['mcapi_transactions_index']['operations'] = array(
  	'title' => t('Operations'),
  	'help' => t('What the user can do to the transaction'),
  	'field' => array(
  		'id' => 'transaction_operations',
  	)
  );

  //TODO Gordon this is gonna be horrible
  //we need to manage the access control, and then to load the parent entity, not the wallet entity.
  $data['mcapi_wallets']['table'] = array(
    'group'  => t('Transaction'),
    'entity type' => 'mcapi_wallets',
    'base' => array(
      'field' => 'wid',
      'title' => t('Wallets'),
      'help' => t("Don't forget to add the parent relationship."),
      'access query tag' => 'transaction_views_access',
      'weight' => 5,
      'defaults' => array(
        'field' => 'wid',
      )
    )
  );

  $data['mcapi_wallets']['xid'] = array(
    'title' => t('Wallet id'), // The item it appears as on the UI,
    'help' => t('The unique database key of the transaction'),
    'field' => array(
      'id' => 'numeric',//this might be 'transaction' if it exists
    ),
    'sort' => array(
      'id' => 'standard',
    ),
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
