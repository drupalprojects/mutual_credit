<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionAccess\Anyone
 */

namespace Drupal\mcapi\Plugin\TransactionAccess;

use Drupal\mcapi\TransactionInterface;

/**
 * Criteria for access to a transaction
 *
 * @TransactionAccess(
 *   id = "anyone",
 *   label = @Translation("Anyone")
 * )
 */
class Anyone {

  function label() {
    //can we pull out the $definition label, above?
    return t("Anyone");
  }

  function checkAccess(TransactionInterface $transaction) {
    return TRUE;
  }

  function viewsAccess($query, $condition, $state) {
    $condition->condition('1', 1);
  }
}
