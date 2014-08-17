<?php

/**
 * @file
 * Provide views data and handlers for transaction.module.
 *
 * @ingroup views_module_handlers
 *
 */

/**
 * Defines the result for hook_views_data().
 */
function mcapi_views_data() {
  $data['mcapi_transaction']['table'] = array(
    'group'  => t('Transaction'),
    'entity type' => 'mcapi_transaction',
    'access query tag' => 'mcapi_views_access',
    'base' => array(
      'field' => 'xid',
      'title' => t('Transactions'),
      'help' => t('Records of transactions between wallets'),
      'weight' => 5,
      'defaults' => array(
        //'field' => 'serial',//only base field itself works at the moment
        'field' => 'xid',
      )
    ),
  	'wizard_id'=> 'transactions',//this links it to the wizard plugin
  );


  $data['mcapi_transaction']['serial'] = array(
    'title' => t('Serial'), // The item it appears as on the UI,
    'help' => t('The serial number of the transaction and dependents'),
    'field' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['mcapi_transaction']['xid'] = array(
    'title' => t('Transaction id'), // The item it appears as on the UI,
    'help' => t('The unique database key of the transaction'),
    'field' => array(
      'id' => 'numeric',//this might be 'transaction' if it exists
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['mcapi_transaction']['payer'] = array(
    'title' => t('Payer'),
    'help' => t('The giving wallet'),
    //the relationship is now to the wallet table, not the user table
    'relationship' => array(
      'id' => 'standard',
      'base' => 'mcapi_wallet',
      'field' => 'wid',
      'label' => t('Payer'),
      'relationship field' => 'payer'
    ),
    'filter' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    ),
    'field' => array(
      'id' => 'standard',
    ),
  );
  $data['mcapi_transaction']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The receiving wallet'),
    //the relationship is now to the wallet table, not the user table
    'relationship' => array(
      'id' => 'standard',
      'base' => 'mcapi_wallet',
      'field' => 'wid',
      'label' => t('Payee'),
      'relationship field' => 'payee'
    ),
    'filter' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    ),
    'field' => array(
      'id' => 'standard',
    ),
  );
  $data['mcapi_transaction']['description'] = array(
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

  $data['mcapi_transaction']['state'] = array(
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

  $data['mcapi_transaction']['type'] = array(
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
  $data['mcapi_transaction']['creator'] = array(
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
  $data['mcapi_transaction']['parent'] = array(
    'title' => t('Parent'),
    'help' => t('Whether the transaction has a parent.'),
    'field' => array(
      'id' => 'boolean',
    ),
    'filter' => array(
      'id' => 'boolean',
    ),
  );
  $data['mcapi_transaction']['created'] = array(
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
  $data['mcapi_transaction']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_year_month',
    ),
  );

  $data['mcapi_transaction']['created_year'] = array(
      'title' => t('Created year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year',
      ),
  );
  $data['mcapi_transaction']['exchange'] = array(
    'title' => t('Current Exchange'),
    'help' => t('Any of the exchanges the current user is a member of'),
    'filter' => array(
      'id' => 'mcapi_current_exchange',
    ),
  );

  //virtual fields
  $data['mcapi_transaction']['transitions'] = array(
    'title' => t('Transitions'),
    'help' => t('What the user can do to the transaction'),
    'field' => array(
      'id' => 'transaction_transitions',
    )
  );
  $data['mcapi_transaction']['exchange'] = array(
    'title' => t('Exchange'),
    'help' => t('The exchange in which the transaction happened'),
    'argument' => array(
      'id' => 'transaction_exchange',
    )
  );

  $data['mcapi_transactions_index']['table'] = array(
  	'group'  => t('Transaction index'),
  	'entity type' => 'mcapi_transaction',
  	'base' => array(
  		'field' => 'xid',
  	  'title' => t('Transaction index'),
  		'help' => t('Transaction index table'),
  		'access query tag' => 'mcapi_views_access',
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
  //the wallet_id and partner_id are being used in a limited way here.
  //with new handlers, would allow some interesting possibilities
  $data['mcapi_transactions_index']['wallet_id'] = array(
  	'title' => t('Wallet ID'),
  	'help' => t('The wallet we are looking at'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'mcapi_wallet',
      'field' => 'wid',
      'label' => t('1st wallet'),
      'relationship field' => 'wallet_id'
    ),
  	'argument' => array(
      'id' => 'standard',
  	),
  	'sort' => array(
  		'id' => 'standard',
  	),
  );
  $data['mcapi_transactions_index']['partner_id'] = array(
  	'title' => t('Partner wallet ID'),
  	'help' => t('The wallet the 1st user traded with'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'mcapi_wallet',
      'field' => 'wid',
      'label' => t('1st wallet'),
      'relationship field' => 'partner_id'
    ),
  	'field' => array(
  		'id' => 'mcapi_wallet_label',
  	),
  	'sort' => array(
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
  $data['mcapi_transactions_index']['exchange'] = array(
    'title' => t('Current Exchange'),
    'help' => t('Any of the exchanges the current user is a member of'),
    'filter' => array(
      'id' => 'mcapi_current_exchange',
    ),
  );
  $data['mcapi_transactions_index']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_year_month',
    ),
  );

  $data['mcapi_transactions_index']['created_year'] = array(
    'title' => t('Created year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'date_year',
    ),
  );
  $data['mcapi_transactions_index']['incoming'] = array(
    'title' => t('Income'),
    'help' => t('The income from the transaction; positive or zero'),
    'field' => array(
      'id' => 'worth',
  		'additional fields' => array('curr_id')
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
  );
  $data['mcapi_transactions_index']['outgoing'] = array(
    'title' => t('Expenditure'),
    'help' => t('The outgoing quantity of the transaction; positive or zero'),
    'field' => array(
      'id' => 'worth',
  		'additional fields' => array('curr_id')
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
  );
  $data['mcapi_transactions_index']['diff'] = array(
    'title' => t('Change in balance'),
    'help' => t('The difference to the balance; positive or negative'),
    'field' => array(
      'id' => 'worth',
  		'additional fields' => array('curr_id')
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
  );
  $data['mcapi_transactions_index']['volume'] = array(
    'title' => t('Volume'),
    'help' => t('The quantity of the transaction; always positive'),
    'field' => array(
      'id' => 'worth',
  		'additional fields' => array('curr_id')
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'numeric',
    ),
  );
  $data['mcapi_transactions_index']['curr_id'] = array(
    'title' => t('Currency'),
    'help' => t('The currency'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'standard',
    ),
  );
  $data['mcapi_transactions_index']['child'] = array(
    'title' => t('Is a child'),
    'help' => t('FALSE if the transaction is the main one with that serial number'),
    'field' => array(
      'id' => 'boolean',
    ),
    'filter' => array(
      'id' => 'boolean',
    ),
  );

  //virtual fields
  $data['mcapi_transactions_index']['balance'] = array(
    'title' => t('Running balance'),
    'help' => t("The sum of the wallet's previous transactions."),
    'field' => array(
      'id' => 'balance',
  		'additional fields' => array('curr_id')
    ),
  );
  $data['mcapi_transactions_index']['transitions'] = array(
  	'title' => t('Transitions'),
  	'help' => t('What the user can do to the transaction'),
  	'field' => array(
  		'id' => 'transaction_transitions',
  	)
  );

  $data['mcapi_wallet']['table'] = array(
    'group'  => t('Wallets'),
    'entity type' => 'mcapi_wallet',
    'base' => array(
      'field' => 'wid',
      'title' => t('Wallets'),
      'help' => t('List of wallets'),
      'weight' => 5,
      'defaults' => array(
        'field' => 'wallet_label',
      )
    ),
  );
  $data['mcapi_wallet']['wid'] = array(
  	'title' => t('Wallet ID'),
  	'help' => t('the unique id of the wallet'),
  	'field' => array(
  		'id' => 'standard',
  	),
  	'argument' => array(
  		'id' => 'standard',
  	)
  );
  $data['mcapi_wallet']['wallet_label'] = array(
  	'title' => t('Label'),
  	'help' => t('the name of the wallet'),
  	'field' => array(
  		'id' => 'mcapi_wallet_label',
  	)
  );
  $data['mcapi_wallet']['owner'] = array(
  	'title' => t('Owner'),
  	'help' => t('The owner of the wallet'),
  	'field' => array(
  		'id' => 'mcapi_wallet_owner',
  	)
  );
  $data['mcapi_wallet']['entity_type'] = array(
  	'title' => t('Owner type'),
  	'help' => t('The entity type of the wallet owner. good for grouping by'),
  	'field' => array(
		  'id' => 'mcapi_owner_type',
  	),
  	'filter' => array(
  		'id' => 'standard',
  	)
  );

  $data['mcapi_exchange']['table'] = array(
    'group'  => t('Exchanges'),
    'entity type' => 'mcapi_exchange',
    'base' => array(
      'field' => 'id',
      'title' => t('Exchanges'),
      'help' => t('List of Exchanges'),
      'weight' => 5,
      'defaults' => array(
        'field' => 'entity_label',
      )
    ),
  );
  $data['mcapi_exchange']['id'] = array(
    'title' => t('Unique id'), // The item it appears as on the UI,
    'help' => t('The unique database key of the exchange'),
    'field' => array(
      'id' => 'standard',
    ),
  );
  $data['mcapi_exchange']['name'] = array(
  	'title' => t('Name'),
  	'help' => t('The name of the exchange'),
  	'field' => array(
  	  //for now we'll make a link by rewriting the field.
  	  //But surely I don't have to create a field handler just to link to the entity
  	  //views/field/EntityLabel.php is so useless - only works with the file entity.
  		'id' => 'standard',
  	)
  );
  $data['mcapi_exchange']['status'] = array(
  	'title' => t('Active'),
  	'help' => t("Active or disabled"),
  	'field' => array(
  		'id' => 'boolean',
  	),
  	'filter' => array(
  		'id' => 'boolean_operator',
  	)
  );
  $data['mcapi_exchange']['visibility'] = array(
  	'title' => t('Visibility'),
  	'help' => t("See, private, restricted and public exchanges, according to current user's access"),
    //TODO can't we use booleans for this?
  	'field' => array(
  		'id' => 'exchange_visibility',
  	),
  	'filter' => array(
  		'id' => 'exchange_visibility',
  	)
  );
  $data['mcapi_exchange']['open'] = array(
  	'title' => t('Open'),
  	'help' => t("Whether the exchange is open to outside trade"),
  	'field' => array(
  		'id' => 'boolean',
  	),
  	'filter' => array(
  		'id' => 'boolean_operator',
  	)
  );
  $data['mcapi_exchange']['membership'] = array(
  	'title' => t('Member count'),
  	'help' => t("The number of 'active' users in the exchange"),
  	'field' => array(
  		'id' => 'exchange_members',
  	  'additional fields' => array('id')
  	)
  );
  // this might not be worth it, especially since we can't sort with it
  //if we wanted to sort we would
  //make a GROUP view on transaction_index and make a relationship to exchanges
  $data['mcapi_exchange']['transactions'] = array(
  	'title' => t('Transaction count'),
  	'help' => t('The number of transactions in a given period'),
  	'field' => array(
  		'id' => 'exchange_transactions',
  	  'additional fields' => array('id')
  	)
  );
  return $data;
}

/*
* implements hook_views_data_alter
*/
function mcapi_views_data_alter(&$data) {
  //todo only show this if max wallets per user = 1;
  $data['users']['trade_stats'] = array(
    'title' => t('Transaction statistic'),
    'help' => t('The balance, trading volume, or transaction count for each user. NB Assumes 1 wallet per user.'),
    'field' => array(
      'id' => 'mcapi_userstat',
    )
  );
  $data['users']['exchanges'] = array(
    'title' => t('Exchange'),
    'help' => t('Filter users by the exchange they are in.'),
    'argument' => array(
      'id' => 'user_exchanges',
    )
  );

  //return;
  //now make a filter for every entity that uses the field 'exchanges'
  //EG for when a node is 'in' an exchange
  foreach (get_exchange_entity_fieldnames() as $entity_type => $fieldname) {
    if (!$fieldname) continue;
    $argument = &$data[$entity_type .'__'. $fieldname][$fieldname .'_target_id']['argument'];
    $argument['help'] = t("Show only what is in exchanges of the current user");
    $argument['id'] = 'entity_exchanges';
    $argument['additional fields'] = array();//no idea how these were helping
    unset($data[$entity_type .'__'. $fieldname]['delta']);
    unset($data[$entity_type .'__'. $fieldname][$fieldname .'_revision_id']);
  }
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

