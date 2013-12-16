<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransactionAccess\PermissionManage
 */

namespace Drupal\mcapi\Plugin\TransactionAccess;

use Drupal\mcapi\TransactionInterface;

/**
 * Links to the transaction certificate
 *
 * @TransactionAccess(
 *   id = "perm_manage",
 *   label = @Translation("Users with 'transact' permission")
 * )
 */
class PermissionManage {

  //TODO how do we access the $definitions from the Annotation?
  function label() {
    return t("Users with '@perm' permission", array('@perm' => 'Manage all transactions'));
  }

  function checkAccess(TransactionInterface $transaction) {
    return user_access('manage all transactions');
  }

  function viewsAccess($query, $condition, $state) {
    $condition->condition(1, user_access('manage all transactions'));
  }
}
