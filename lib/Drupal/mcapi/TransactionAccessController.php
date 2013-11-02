<?php

/**
 * @file
 * Contains \Drupal\simple_access\GroupAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
/**
 * Defines an access controller for the contact category entity.
 *
 * @see \Drupal\simple_access\Entity\Group.
 */
class TransactionAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    //FIXME: Make this work properly
    return TRUE;
  }
}