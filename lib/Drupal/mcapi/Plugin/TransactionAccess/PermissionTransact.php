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
 * @Operation(
 *   id = "permTransact",
 *   label = @Translation("Users with 'transact' permission")
 * )
 */
class PermissionTransact {

  function __construct() {
    die('constructing permissionTransact');

  }
  //TODO how do we access the $definitions from the Annotation?
  function label() {
    return t("Users with 'transact' permission");
  }

  function checkAccess(TransactionInterface $transaction) {
    return user_access('transact');
  }
}
