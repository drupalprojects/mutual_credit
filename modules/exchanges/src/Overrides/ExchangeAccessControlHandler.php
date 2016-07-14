<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\group\Entity\Access\GroupAccessControlHandler;
use Drupal\group\Access\GroupAccessResult;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Exchange type of group entity.
 *
 * @todo think this through some more.
 */
class ExchangeAccessControlHandler extends GroupAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($entity->bundle() == 'exchange') {
      switch ($operation) {
        case 'view':
          return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view group');

        case 'update':
          return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'edit group');

        case 'delete':
          // An exchange with transactions simply cannot be deleted.
          return GroupAccessResult::forbiddenIf($entity->getContent('group_transactions'));
      }
      return AccessResult::neutral();
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
