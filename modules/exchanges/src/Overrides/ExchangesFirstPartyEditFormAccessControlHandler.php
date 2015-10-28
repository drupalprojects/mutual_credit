<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\FirstPartyEditFormAccessControlHandler.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\mcapi\Exchange;
use Drupal\mcapi_1stparty\FirstPartyEditFormAccessControlHandler;


/**
 * Access control for first party transaction forms, according to the form's own settings
 *
 */
class ExchangesFirstPartyEditFormAccessControlHandler extends FirstPartyEditFormAccessControlHandler {
  
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $op, AccountInterface $account) {
    $result = parent::checkAccess($entity, $op, $account);
    if (Exchange::in() && $entity->exchange) {
      //only the exchange owner can edit the form
      if (entity_load('mcapi_exchange', $entity->exchange)->getOwnerId() == $account->id()) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }

}
