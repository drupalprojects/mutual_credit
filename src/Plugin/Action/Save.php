<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Confirmation form for creating a transaction.
 *
 * @Action(
 *   id = "mcapi_transaction.save_action",
 *   label = @Translation("Save a transaction to disk"),
 *   type = "mcapi_transaction"
 * )
 */
class Save extends TransactionActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    if ($object->id()) {
      $result = FALSE;
    }
    else {
      // Can't think of what other conditions might be needed.
      $result = TRUE;
    }
    if ($return_as_object) {
      return $result ? AccessResult::allowed() : AccessResult::forbidden();
    }
    return $result;
  }

}
