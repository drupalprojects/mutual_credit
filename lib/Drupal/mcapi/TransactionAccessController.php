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
 * Defines an access controller option for the mcapi_transaction entity.
 *
 * @see \Drupal\simple_access\Entity\Group.
 */
class TransactionAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $transaction, $op, $langcode, AccountInterface $account) {
    if ($op == 'op') {
      //there is probably a better way of writing the router so the op is passed as a variable
      $op = \Drupal::request()->attributes->get('op');
    }
    $operations = transaction_operations(TRUE);
    return $operations[$op]->opAccess($transaction);
  }


}
