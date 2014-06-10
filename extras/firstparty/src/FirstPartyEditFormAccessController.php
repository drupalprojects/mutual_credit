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
 * Default access control for first party transaction forms.
 * Grants access if the user is in any active exchange
 *
 */
class FirstPartyEditFormAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $transition, $langcode, AccountInterface $account) {
    //grant access if the user is in this exchange
    if(referenced_exchanges(NULL, TRUE)) return TRUE;
    //or if the user is system-wide admin
    return $account->hasPermission($this->entityType->getAdminPermission());
  }

}
