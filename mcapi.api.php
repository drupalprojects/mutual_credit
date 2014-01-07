mcapi_ <?php
/**
 * @file
 * Formal description of transaction handling function and Entity controller functions
 *
 * N.B. transaction' can have 3 different meanings
 *  a database transaction (not relevant to this document)
 *  Fieldable entity with one or more '$items' each a in different currency. This is what views works with
 *  A transaction cluster is an array of the previous 'transactions',
 *  usually before they are written to the db, where they will have the same serial number and state
 *    The first transaction in a cluster 'volitional' and the rest, 'dependent',
 *   which means they were created automatically, from the volitional
 *   the dependent transactions share a serial number and state, but probably have a different 'type'
 *  When a transaction is loaded from the db, the dependents are put into (array)$transaction->dependents.
 */

/*
 * Community Accounting API FOR MODULE DEVELOPERS
 * WRAPPER FUNCTIONS
 * These 3 wrapper functions around the following transaction controller API
 * are mostly concerned with managing transactions as clusters sharing the same serial number
 * Module developers should use these functions wherever possible.
 */


/*
 * create child transactions and return an array of them
 * then will then be added to the transaction->children
 */
function hook_transaction_presave(TransactionInterface $transaction){
  return array();
}

/*
 * create child transactions
 * return an array of transaction objects
 */
function hook_transaction_children(TransactionInterface $transaction){}

/*
 * The default entity controller supports 3 undo modes
 * Utter delete
 * Change state to erased
 * Create counter-transaction and set state of both to TRANSACTION_STATE_UNDONE
 * NB this function goes on to call hook_transaction_undo()
 */
try {
  $transaction->undo();
}
catch(exception $e){}

/*
 * filter transactions
 * returns an array of serial numbers keyed by xid
 *
 * arguments, in any order can be
 * serial, integer or array
 * from // unixtime
 * to //unixtime
 * state integer or array
 * creator integer or array($uids)
 * payer integer or array($uids)
 * payee integer or array($uids)
 * involving integer or array($uids)
 * currcode string or array($currcodes)
 * type string or array($types)
 * no pager is provided in this function
 * views is much better
 * this hasn't been used yet
 */
$conditions = array('serial' => array('AB123', 'AB124'));
//or
$conditions = array('involving' => array(234, 567));

/*
 * this is a substitute for views
 * arguments:
 *  $conditions - an array of transaction properties, each with a value or array of values to filter for
 *  $offset - used for paging
 *  $limit - used for paging
 *  $fieldapi_conditions - more conditions for testing against loaded transactions.
 *   NB this could be expensive in memory
 *   NB paging is ignored
 *   NB in multiple cardinality fields, only the first value is filtered for
 */
$array  = transaction_filter($conditions, $offset, $limit);



/*
 *
 * Retrieves transaction summary data for a user in a given currency
 *
 * This data can also be obtained through various views fields, especially in the mcapi_index_views module
 * $filters are same as in drupal database api, each an array like ($fieldname, $value, $operator),
 * applicable to the mcapi_transactions table (worth field is not supported)
 * where the fieldname is from mcapi_transactions table and the operator is optional.
 * If there are no conditions passed then only transactions in a positive STATE are counted.
 *
 * Returns an array with the following keys
 * - balance, sum of all transactions with state > 0
 * - gross_in, sum of all incoming transactions with state > 0
 * - gross_out, sum of all outgoing transactions with state >0
 * - count, number of transactions with state > 0
 * - partners, number of distinct trading partners using transactions with state > 0
 */
//not implemented yet
$account->getTradeSummary();


/*
 * list of hooks called in this module
 * no need to put hook_info coz that's just for lazy loading
 */

//check the transactions and the system integrity after the transactions would go through
//do NOT change the transaction
function hook_mcapi_transaction_validate($transactions){}

//respond to the creation of a transaction
function hook_transaction_post_insert($transaction){}

//preparing a transaction for rendering
function hook_transactions_view($transactions, $view_mode, $suppress_ops){}

//respond to the changing of a transaction
function hook_transaction_update($serial){}

//respond to the removal, or undoing of a transaction
function hook_transaction_undo($serial){}

/*
 * Transaction access plugins: See lib/Drupal/mcapi/Plugin/TransactionAccess
 * One plugin is a rule for granting access to a transaction.
 * All the rules appear together as checkboxes and and access is granted if ANY of them returns TRUE
 */

//declare transaction states
function hook_mcapi_info_states(){
  return array(
    99 => array(//ensure this number doesn't clash with existing states
      'name' => t('Rejected'),
      'description' => t('transaction was terminated by payee'),

    ),
  );
}
//declare transaction types, perhaps one for each workflow process
function hook_mcapi_info_types(){
  return array(
    'donate' => t('Donate'),
    'charge' => t('Charge'),
    'rebate' => t('Rebate'),
  );
}

//declare permissions to go into the community accounting section of the drupal permissions page
function hook_mcapi_info_drupal_permissions(){}


/**
 * Viewing a transaction
 * pass the transaction entity, then the view_mode
 */
entity_view($transaction, 'certificate');
entity_view($transaction, 'sentence');//defaults to the saved variable mcapi.misc.sentence
entity_view($transaction, 'some arbitrary twig {{ payee }}');

/**
 * Transaction operations: See lib/Drupal/mcapi/Plugin/Operation
 * User stories are built up using transaction operations to make a workflow.
 * Operations allow transactions to be modified in a very controlled way, such as changing the state, or adding a comment
 * The experience of each operation can be configured precisely
 * Each operation triggers a transaction update event
 */

/*
 * Worths & Worth field
 * A new field type is created to store the quantity of the transaction and the currency together
 * It is commonly used as an array so that the application natively handles transactions with multiple currencies (mixed transactions)
 * EVERY transaction has a property called 'worths'
 * One 'worths' instance cannot store more than one flow in one currency
 * Worth values are not themed, but taken from the object
 * NB for now the worths array is found INSIDE $worths[0] Hopefully this will change in d8 Core.
 */
$worths[0]->__toString();//give something like "1H 00mins; $4.22"
foreach ($worths[0] as $worth) {
  $string = $worth->__toString();
  $currencyObject = $worth->currency;
  $numeric = $worth->value;//this is the stored integer value
}
//when building a form:
$element['worth'] = array(
  '#type' => 'worth',
  '$default_value' => array(
    'currcode' => 'credunit',
    'value' => 3600
  )
);
//or
$element['worths'] = array(
  '#type' => 'worths',
  '$default_value' => array(
    'credunit' => 3600,
    'escro' => 4.22
  ),
);

/*
 * Currency Types
 * All worth values are stored as integers,
 * The currency type plugin controls how the user interacts with those numbers
 * both for entering values with widgets and rendering values.
 * Are they seconds while the user can only trade hours?
 * Or are they cents?
 */
 $currency->render(3600);//might return "1h" or $36.00
 $transaction->worths[0]->__to_string();