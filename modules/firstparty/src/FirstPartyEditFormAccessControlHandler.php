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
 * Access control for first party transaction forms
 *
 */
class FirstPartyEditFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    return AccessResult::allowedIfHasPermission($account, 'configure mcapi');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'configure mcapi');
  }
}
