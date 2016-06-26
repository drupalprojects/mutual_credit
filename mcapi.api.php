<?php
/**
 * @file
 * Formal description of transaction handling and Entity controller functions.
 *
 * N.B
 * The mcapi_transaction entity has a children property, which contains
 * transactions with a parent value. The parent and the children are saved side
 * by side in the database, with a 'parent xid' property. Of entities with the
 * same serial number, one should have a 'parent' property of 0, and all the
 * others should have that entities xid as their parent. The functions here all
 * assume the transaction is fully loaded, with children, unless otherwise
 * stated.
 *
 * When saving transactions you can ->validate() first and handle the ensuing
 * $violations, or just save() and hope nothing breaks.
 */

/**
 * HOOKS.
 */

/**
 * Retrieves transaction summary data for a user in a given currency.
 *
 * This data can also be obtained through various views fields, especially in
 * the mcapi_index_views module. $filters are same as in drupal database api,
 * each an array like ($fieldname, $value, $operator), applicable to the
 * mcapi_transactions table (worth field is not supported), where the fieldname
 * is from mcapi_transactions table and the operator is optional.
 * If there are no conditions passed then only transactions in a positive STATE
 * are counted.
 *
 * Returns an array with the following keys
 * - balance, sum of all transactions with state > 0
 * - gross_in, sum of all incoming transactions with state > 0
 * - gross_out, sum of all outgoing transactions with state >0
 * - count, number of transactions with state > 0
 * - partners, number of distinct trading partners using transactions with
 * state > 0
 */


/**
 * Viewing a transaction
 * view modes are 'certificate, which is default, sentence, or an arbitrary twig
 * string can be passed.
 */
$renderable = $transaction->view('certificate');

/**
 * Worth element
 * a drupal element #type => worth contains MANY transaction flows in different
 * currencies.
 */
$element['max'] = array(
  '#title' => t('Maximum balance'),
  '#description' => t('Must be greater than 1.'),
  '#type' => 'worth',
  // We key the default value with the curr_id to make the saved settings easier
  // to read.
  '#default_value' => array(
    0 => array(
      'curr_id' => 'cc',
      'value' => 999,
    ),
    1 => array(
      'curr_id' => 'veur',
      'value' => 101,
    ),
  ),
// Curr ids not needed here.
  '#placeholder' => array(0 => 99, 1 => 10),
  '#weight' => 1,
);
