<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\FirstPartyEditFormAccessControlHandler.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;


/**
 * Access control for first party transaction forms, according to the form's own settings
 *
 */
class FirstPartyEditFormAccessControlHandler extends EntityAccessControlHandler {
  
  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermission($account, 'configure mcapi');
  }
  
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $op, $langcode, AccountInterface $account) {
    //only $accounts with wallets can access the form
    if ($account->hasPermission('configure mcapi')) {
      return AccessResult::allowedIfHasPermission($account, 'configure mcapi');
    }
    elseif ($entity->exchange) {
      //only the exchange owner can edit the form
      if (entity_load('mcapi_exchange', $entity->exchange)->getOwnerId() == $account->id()) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }

}
