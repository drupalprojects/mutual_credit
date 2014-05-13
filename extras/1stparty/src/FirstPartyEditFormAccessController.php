<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\FirstPartyEditFormAccessController.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;


/**
 * Defines a default implementation for entity access controllers.
 */
class FirstPartyEditFormAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    //grant access if the user is in this exchange
    if ($entity->exchange && in_array($entity->exchange, referenced_exchanges())) return TRUE;
    //or if the user is system-wide admin
    return $account->hasPermission($this->entityType->getAdminPermission());
  }

}
