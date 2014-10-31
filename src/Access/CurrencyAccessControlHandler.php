<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\CurrencyAccessControlHandler.
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller for the Currency entity
 *
 * @see \Drupal\mcapi\Entity\Currency.
 */
class CurrencyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowed()->cachePerRole();
      case 'create':
      case 'delete':
      case 'update':
        if ($account->hasPermission('configure mcapi')) return AccessResult::allowed()->cachePerRole();
        elseif ($entity->id()) {//i.e it is already saved
          if ($account->id() == $entity->getOwnerId()) return AccessResult::allowed()->cachePerUser();
          else return AccessResult::forbid()->cachePerUser();
        }
        else {
          //who can create new currencies?
          drupal_set_message('Need to sort out Currency create access script');
          return AccessResult::forbid()->cachePerUser();
        }
        break;
    };
    return $result;
  }

}
