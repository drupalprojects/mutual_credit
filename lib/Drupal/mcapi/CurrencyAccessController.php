<?php

/**
 * @file
 * Contains \Drupal\mcapi\CurrencyAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the contact category entity.
 *
 * @see \Drupal\mcapi\Entity\Currency.
 */
class CurrencyAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    //TODO: If the currency has non-deleted transaction then stop the currency from being deleted.
    return $account->hasPermission('configure all currencies') || (isset($entity->uid) && $entity->uid == $account->id());
  }

}
