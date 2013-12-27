<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionAccess\IsPayee
 */

namespace Drupal\mcapi\Plugin\TransactionAccess;

use Drupal\mcapi\TransactionInterface;

/**
 * Criteria for access to a transaction
 *
 * @TransactionAccess(
 *   id = "is_payee",
 *   label = @Translation("The payee")
 * )
 */
class IsPayee {

  function label() {
    //can we pull out the $definition label, above?
    return t("The payee");
  }

  function checkAccess(TransactionInterface $transaction) {
    return \Drupal::currentUser()->id() == $transaction->payee->value;
  }

  //SELECT transactions WHERE (currency = whatever) AND (state = $state AND ($condition))
  function viewsAccess($query, $condition, $state) {
    $condition->condition('mcapi_transactions.payee', \Drupal::currentUser()->id());
  }
}
