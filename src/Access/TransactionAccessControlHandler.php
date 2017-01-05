<?php

namespace Drupal\mcapi\Access;

use Drupal\mcapi\TransactionOperations;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $wids = \Drupal::entityQuery('mcapi_wallet')
      ->condition('holder_entity_type',  'user')
      ->condition('holder_entity_id',  $account->id())
      ->execute();
    return  $wids ?
      AccessResult::Allowed()->addCacheableDependency($account) :
      AccessResult::Forbidden()->addCacheableDependency($account);
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    if ($operation == 'view label') {
      $operation = 'view';
    }
    if ($operation === 'view' && $account->hasPermission('view all transactions')) {
      // @todo URGENT. Handle the named payees and payers
      $result = AccessResult::allowed()->cachePerUser();
    }
    if ($action =  TransactionOperations::loadOperation($operation)) {
      $result = $action->getPlugin()
        ->access($transaction, $account, TRUE)
        ->cachePerUser()
        ->addCacheableDependency($transaction);
    }
    else {
      $result = AccessResult::forbidden()->cachePerPermissions();
      debug('No action for transaction access operation: '.$operation);
    }
    return $return_as_object ? $result: $result->isAllowed();
  }

}
