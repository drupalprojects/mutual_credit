<?php

namespace Drupal\mcapi_exchanges\Overrides;

/**
 * @file
 * Contains \Drupal\mcapi\Access\TransactionAccessControlHandler.
 */

use Drupal\mcapi\Access\TransactionAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\mcapi\Mcapi;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class ExchangeTransactionAccessControlHandler extends TransactionAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if (!$result->isForbidden()) {
      $mems = \Drupal::service('group.membership_loader')->loadByUser($account);
      foreach ($mems as $membership) {
        $group = $membership->getGroup();
        if ($group->getGroupType()->id() == 'exchange') {
          // @todo check if the exchange is active and has a currency
          if ($group->currencies->count()) {
            $result = AccessResult::allowed()->cachePerUser();
          }
        }
      }
    }
    print_r($result);die();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation === 'view' and $account->hasPermission('view all transactions')) {
      // @todo URGENT. Handle the named payees and payers
      return $return_as_object ? AccessResult::allowed()->cachePerUser() : TRUE;
    }
    return Mcapi::transactionActionLoad($operation)
      ->getPlugin()
      ->access($transaction, $account, $return_as_object)
      ->cachePerUser();
  }

}
