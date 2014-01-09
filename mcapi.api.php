<?php
/**
 * @file
 * Formal description of transaction handling function and Entity controller functions
 *
 * N.B
 * The mcapi_transaction entity has an entity_reference field, children, which contains similar entities
 * The parent and the children are saved side by side in the database, with a 'parent xid' property
 * Of entities with the same serial number, one should have a 'parent' property of 0, and all the others should have that entities xid as their parent.
 * The functions here all assume the transaction is fully loaded, with children, unless otherwise stated
 */

/*
 * HOOKS
 */

/**
 * @return array
 *   permission array as in hook_permission
 */
function hook_mcapi_info_drupal_permissions(){}

/**
 * Let your module validate the transaction. Don't throw errors, but add TransactionException objects to $transaction->exceptions
 *
 * $param TransactionInterface $transaction
 *   this CAN be edited
 */
function hook_mapi_transaction_validate($transaction){}

/**
 * generate the $transaction->children
 *
 * $param TransactionInterface $transaction
 *
 * @return array
 *   an array of transactions to be put in the $children property
 */
function hook_mapi_transaction_children($transaction){}

/**
 * alter the $transaction->children
 *
 * $param array $children
 * $param TransactionInterface $transaction
 *   Editing this will have no effect
 */
function hook_mapi_transaction_children_alter($children, $cloned_transaction){}

/**
 * Let other modules respond to a transaction operation
 *
 * @param $transaction
 * @param $context
 *   an array consisting of op: the operation plugin name; old_state: the state before the operation happened; config: the plugin configuration;
 * @return array
 *   permission array as in hook_permission
 */
function hook_mapi_transaction_operated($transaction, $context){

}


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
 *
 */

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