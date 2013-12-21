<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionAccess\PermissionTransact
 */

namespace Drupal\mcapi\Plugin\TransactionAccess;

use Drupal\mcapi\TransactionInterface;

/**
 * Links to the transaction certificate
 *
 * @TransactionAccess(
 *   id = "perm_transact",
 *   label = @Translation("Users with 'transact' permission")
 * )
 */
class PermissionTransact {

  //TODO how do we access the $definitions from the Annotation?
  function label() {
    return t("Users with '@perm' permission", array('@perm' => 'Transact'));
  }

  function checkAccess(TransactionInterface $transaction) {
    return user_access('transact');
  }

  function viewsAccess($query, $condition, $state) {
    $condition->condition(1, user_access('transact'));
  }
}
