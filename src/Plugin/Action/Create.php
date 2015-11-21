<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Action\Create
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
 *   id = "mcapi_transaction.create_action",
 *   label = @Translation("Confirm a new transaction"),
 *   type = "mcapi_transaction"
 * )
 */
class Create extends \Drupal\mcapi\Plugin\TransactionActionBase {

    
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->id()) $result = FALSE;
    else $result = \Drupal\mcapi\Access\TransactionAccessControlHandler::enoughWallets($account);
    if ($return_as_object) {
      return $result ? AccessResult::allowed() : AccessResult::forbidden();
    }
    return $result;
  }
}
