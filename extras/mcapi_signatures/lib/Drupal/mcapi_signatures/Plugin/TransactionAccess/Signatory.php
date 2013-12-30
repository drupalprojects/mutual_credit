<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionAccess\Signatory
 */

namespace Drupal\mcapi_signatures\Plugin\TransactionAccess;

use Drupal\mcapi\TransactionInterface;

/**
 * Criteria for access to a transaction
 *
 * @TransactionAccess(
 *   id = "is_signatory",
 *   label = @Translation("Any signatory")
 * )
 */
class Signatory {

  function label() {
    //can we pull out the $definition label, above?
    return t("A signatory");
  }

  function checkAccess(TransactionInterface $transaction) {
    if (isset($transaction->signatures)) {
      return array_key_exists(\Drupal::currentUser()->id(), $transaction->signatures);
    }
  }

  //SELECT transactions WHERE (currency = whatever) AND (state = $state AND ($condition))
  function viewsAccess($query, $condition, $state) {
    //The join may have been created already by the views schema
    if (!array_key_exists('mcapi_signatures', $query->tables)) {
//    $query->addJoin('LEFT', 'mcapi_signatures', 'mcapi_signatures', 'mcapi_signatures.serial = mcapi_transactions.serial');
      //this adds the table but doesn't affect th query
      $query->ensureTable('mcapi_signatures');
    }
    //if distinct won't work it could make a nasty mess
    //$query->distinct();
    $condition->condition('mcapi_signatures.uid', \Drupal::currentUser()->id());
  }
}
