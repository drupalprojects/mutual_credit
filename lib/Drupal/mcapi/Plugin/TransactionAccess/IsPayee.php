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
 * @Operation(
 *   id = "IsPayee",
 *   label = @Translation("The payee")
 * )
 */
class IsPayee  extends ConfigEntityBase {

  function label() {
    //can we pull out the $definition label, above?
    return t("The payee");
  }

  function checkAccess(TransactionInterface $transaction) {
    return $GLOBALS['user']->id() == $tansaction->payee->value;
  }

  function viewsAccess(TransactionInterface $transaction) {

  }
}
