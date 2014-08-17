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
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $transition, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    if ($transition == 'transition') {
      //there is probably a better way of writing the router so the op is passed as a variable
      $transition = \Drupal::request()->attributes->get('transition');
    }
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    if ($transition = transaction_transitions($transition)) {
      return $transition->opAccess($transaction, $account);
    }
    return FALSE;
  }
}
