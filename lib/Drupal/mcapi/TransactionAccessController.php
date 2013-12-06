<?php

/**
 * @file
 * Contains \Drupal\simple_access\GroupAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
/**
 * Defines an access controller for the contact category entity.
 *
 * @see \Drupal\simple_access\Entity\Group.
 */
class TransactionAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $transaction, $op, $langcode, AccountInterface $account) {
    if (!$op) {
      $op = \Drupal::request()->attributes->get('_raw_variables')->get('op');
    } 
    //might want to store the operations in the object since this is likely to be called many times
    $operations = transaction_operations();
    mcapi_operation_include($operations[$op]);

    foreach ($transaction->worths[0] as $item) {
      if ($operations[$op]['access callback']($op, $transaction, $item->currency)) {
        continue;
      }
      return FALSE;
    }
    return TRUE;
  }
}
