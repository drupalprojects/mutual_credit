<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionAccess\IsPayer
 */

namespace Drupal\mcapi\Plugin\TransactionAccess;

use Drupal\mcapi\TransactionInterface;

/**
 * Criteria for access to a transaction
 *
 * @TransactionAccess(
 *   id = "is_payer",
 *   label = @Translation("The payer")
 * )
 */
class IsPayer {

  function label() {
    //can we pull out the $definition label, above?
    return t("The payer");
  }

  function checkAccess(TransactionInterface $transaction) {
    return \Drupal::currentUser()->id() == $transaction->payer->value;
  }

  //SELECT transactions WHERE (currency = whatever) AND (state = $state AND ($condition))
  function viewsAccess($query, $condition, $state) {
    $condition->condition('mcapi_transactions.payer', \Drupal::currentUser()->id());
  }
}
