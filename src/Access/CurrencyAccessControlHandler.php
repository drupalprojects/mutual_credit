<?php

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller for the Currency entity.
 *
 * @see \Drupal\mcapi\Entity\Currency.
 */
class CurrencyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowed()->cachePerPermissions();

      case 'create':
      case 'update':
        if ($account->hasPermission('configure mcapi')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        // i.e it is already saved.
        elseif ($entity->id()) {
          if ($account->id() == $entity->getOwnerId()) {
            $result = AccessResult::allowed()->cachePerUser();
          }
          else {
            $result = AccessResult::forbidden()->cachePerUser();
          }
        }
        else {
          // Who can create new currencies?
          debug('Need to sort out Currency create access script');
          $result = AccessResult::forbidden()->cachePerUser();
        }
        break;

      case 'delete':
        // @todo inject service entity.query.config
        $count = \Drupal::entityQuery('mcapi_transaction')
          ->condition('worth.curr_id', $entity->id())
          ->count()
          ->execute();
        $result = $count ?
          AccessResult::forbidden() :
          AccessResult::allowed();
    };
    return $result;
  }

}
