<?php
/**
 * @file
 * Formal description of transaction handling function and Entity controller functions
 *
 * N.B
 * The mcapi_transaction entity has a children property, which contains transactions with a parent value
 * The parent and the children are saved side by side in the database, with a 'parent xid' property
 * Of entities with the same serial number, one should have a 'parent' property of 0, and all the others should have that entities xid as their parent.
 * The functions here all assume the transaction is fully loaded, with children, unless otherwise stated
 */

//When saving transactions you can ->validate() first and handle the ensuing $violations, or just save() and hope nothing breaks

/**
 * HOOKS
 */

/**
 * Let your module validate the transaction. Don't throw errors, but add TransactionException objects to $transaction->exceptions
 *
 * $param TransactionInterface $transaction
 *   this CAN be edited
 */
function hook_mcapi_transaction_validate($transaction){}

/**
 * generate the $transaction->children
 *
 * $param TransactionInterface $transaction
 *
 * @return array
 *   an array of transactions to be put in the $children property
 */
function hook_mcapi_transaction_children($transaction){}

/**
 * alter the $transaction->children
 *
 * $param array $children
 * $param TransactionInterface $transaction
 *   Editing this will have no effect
 */
//function hook_mcapi_transaction_children_alter($children, $cloned_transaction){}

/**
 * Let other modules respond to a transaction transition
 *
 * @param $transaction
 *   the Transaction entity
 *
 * @param $context
 *   an array consisting of op: the transition plugin name; old_state: the state before the transition happened; config: the plugin configuration;
 *
 * @return array
 *   permission array as in hook_permission
 */
function hook_mcapi_transition($transaction, $context){
  $subject = $context['config']['subject'];
  $body = $context['config']['body'];
  if ($subject || $body) {
    $params += array(
        'subject' => $subject,
        'body' => $body,
        'cc' => $context['config']['cc'],
        'transaction' => $this
    );
    drupal_mail('mcapi', 'transition', $to = '???', $language->language, $params);
  }
}

/**
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


/**
 * Viewing a transaction
 * pass the transaction entity, then the view_mode
 */
entity_view($transaction, 'certificate');
entity_view($transaction, 'sentence');//defaults to the saved variable mcapi.misc.sentence
entity_view($transaction, 'some arbitrary twig {{ payee }}');

/**
 * Transaction transitions: See lib/Drupal/mcapi/Plugin/Transition
 * User stories are built up using transaction transitions to make a workflow.
 * Transitions allow transactions to be modified in a very controlled way, such as changing the state, or adding a comment
 * The experience of each transition can be configured precisely
 * Each transition triggers a transaction update event
 */

/**
 * Worth element
 * a drupal element #type => worth contains MANY transaction flows in different currencies.
 * for an
 */
$element['max'] = array(
  '#title' => t('Maximum balance'),
  '#description' => t('Must be greater than 1.'),
  '#type' => 'worth',
  //we key the default value with the curr_id to make the saved settings easier to read
  '#default_value' => array(
    0 => array(
      'curr_id' => 'cc',
      'value' => 999
    ),
    1 => array(
      'curr_id' => 'veur',
      'value' => 101
    )
  ),
  '#placeholder' => array(0 => 99, 1 => 10),//curr ids not needed here
  '#weight' => 1,
  '#min' => 1
);
//returns a value like
array(
  0 => array(
    'curr_id' => 'cc',
    'value' => 999
  ),
  1 => array(
    'curr_id' => 'veur',
    'value' => 101
  )
);

/**
 * Worth fieldType
 * It is commonly used as an array so that the application natively handles transactions with multiple currencies (mixed transactions)
 * This datatype is hard coded to the transaction as an FieldAPI ItemList, allowing many values in one entity.
 */
$contentEntity->get('worth')->value;//gets an array of worth arrays as above
$contentEntity->worth->value;//gets an array of worth arrays as above
