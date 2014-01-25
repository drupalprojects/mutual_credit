<?php

/**
 * @file
 * Contains \Drupal\simple_access\TransactionAccessController.
 * there is an access checking hierarchy:
 * $entity->access can be called from anywhere. it checks the cache and prepares for
 * $entity->createAccess, which checks cache and prepares context before invoking hooks
 * $entity->checkCreateAccess
 *
 * then there is
 * $entity->checkAccess, which seems concerned only with entity admin permission
 *
 * so with all that I'm guessing a bit how to build this.
 * we probably don't want to mess too much with transaction access since wallets is where it is at, now.
 * the transaction operations are all handled by plugins we just need to invoke them
 * except for the create operation.
 *
 *
 */

namespace Drupal\mcapi;

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
    //you can view a transaction if you can view either the payer or payee wallets
    if ($op == 'view') {
      return $transaction->payer->entity->access('view', $account) || $transaction->payee->entity->access('view', $account);
    }

    return transaction_operations($op)->opAccess($transaction, $account);
  }
}
