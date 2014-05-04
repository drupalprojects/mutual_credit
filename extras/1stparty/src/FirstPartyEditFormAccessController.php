<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\FirstPartyEditFormAccessController.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Entity\EntityAccessController;


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
    return $account->hasPermission('configure mcapi');
  }

}
