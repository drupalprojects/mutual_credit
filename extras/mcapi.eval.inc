<?php

/**
 * @file
 * Contains rules integration for the mcapi module needed during evaluation.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Condition: Check for selected content types
 */
function transaction_is_of_type($transaction, $types) {
  return in_array($transaction->type, $types);
}

/**
 * Condition: Check if the mcapi is published
 */
function transaction_is_of_state($transaction, $states) {
  return in_array($transaction->state, $states);
}
/*
 * action callback
 * generate a new transaction and add it to $cluster
 */
function mcapi_dependent_transaction_rules_action($worth, $payer, $payee, $settings, RulesState $state, RulesPlugin $element) {
  //I really hope one of these arguments contains the passed transaction cluster...
  debug(func_get_args(), 'mcapi_dependent_transaction_rules_action');

}

/*
 * action callback
 * save a new transaction
 */
function mcapi_new_transaction_rules_action($worth, $payer, $payee, $settings, $alter_cluster, $settings, RulesState $state, RulesPlugin $element) {
  $transaction = entity_metadata_create_transaction();
  $transaction->worth[LANGUAGE_NONE] = $worth;
  $transaction->payer = $payer;
  $transaction->payee = $payee;
  //the TRUE assumes the transaction will validate.
  //when this is working we need to handle failed validations, using the failed_forced state
  if ($alter_cluster)  {
    transaction_insert_new($transaction, TRUE);
  }
  else {
    $transactions = array($transaction);
    transactions_insert($transactions, TRUE);
  }
}