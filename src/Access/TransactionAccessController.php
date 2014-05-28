<?php

namespace Drupal\mcapi\Access;

/**
 * @file
 * Contains \Drupal\mcapi\Access\TransactionAccessController.
 */

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\Language;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $op, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    if ($op == 'op') {
      //there is probably a better way of writing the router so the op is passed as a variable
      $op = \Drupal::request()->attributes->get('op');
    }
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    return transaction_transitions($op)->opAccess($transaction, $account);
  }
}
