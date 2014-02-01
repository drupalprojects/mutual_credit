<?php

/**
 * @file
 * Contains \Drupal\mcapi\WalletStorageController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\mcapi\WalletStorageControllerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * This is an unfieldable content entity. But if we extend the DatabaseStorageController
 * instead of the FieldableDatabaseStorageController then the $values passed to the create
 * method work very differently, putting NULL in all database fields
 */
class WalletStorageController extends FieldableDatabaseStorageController implements WalletStorageControllerInterface {

  /**
   * @see \Drupal\mcapi\WalletStorageControllerInterface::getWalletIds()
   */
  function getWalletIds(EntityInterface $entity) {
    return db_select('mcapi_wallets', 'w')
      ->fields('w', array('wid'))
      ->condition('entity_type', $entity->entityType())
      ->condition('pid', $entity->id())
      ->execute()->fetchCol();
  }

  /**
   * @see \Drupal\mcapi\WalletStorageControllerInterface::spare()
   */
  function spare(EntityInterface $owner) {
    //finally we check the number of wallets already owned against the max for this entity type
    $wids = $this->getWalletIds($owner);
    $bundle = $owner->entityType().':'.$owner->bundle();
    $max = \Drupal::config('mcapi.wallets')->get('entity_types.'.$bundle);
    if (count($wids) < $max) return TRUE;
    return FALSE;
  }

}
