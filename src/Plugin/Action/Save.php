<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Action\Save
 *
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Confirmation form for creating a transaction
 *
 * @Action(
 *   id = "mcapi_transaction.save_action",
 *   label = @Translation("Save a transaction to disk"),
 *   type = "mcapi_transaction"
 * )
 */
class Save extends \Drupal\mcapi\Plugin\TransactionActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->id()) {
      $result = FALSE;
    }
    else {
      $result = $this->entityTypeManager->getAccessControlHandler('mcapi_transaction')->enoughWallets($account);
    }
    if ($return_as_object) {
      return $result ? AccessResult::allowed() : AccessResult::forbidden();
    }
    return $result;
  }
}
