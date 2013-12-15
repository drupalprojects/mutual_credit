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
 * @Operation(
 *   id = "IsPayer",
 *   label = @Translation("The payer")
 * )
 */
class IsPayer {

  function label() {
    //can we pull out the $definition label, above?
    return t("The payer");
  }

  function checkAccess(TransactionInterface $transaction) {
    return $GLOBALS['user']->id() == $tansaction->payer->value;
  }

  function viewsAccess(TransactionInterface $transaction) {

  }
}
