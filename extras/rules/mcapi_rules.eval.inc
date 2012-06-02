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
function rules_condition_mcapi_is_of_type($transaction, $types) {
  return in_array($transaction->type, $types);
}

/**
 * Condition: Check if the mcapi is published
 */
function rules_condition_mcapi_is_of_state($transaction, $states) {
  return in_array($transaction->state, $states);
}

