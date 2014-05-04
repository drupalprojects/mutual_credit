<?php

/**
 * @file
 * Contains \Drupal\mcapi\WalletStorage.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\mcapi\WalletStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * This is an unfieldable content entity. But if we extend the EntityDatabaseStorage
 * instead of the ContentEntityDatabaseStorage then the $values passed to the create
 * method work very differently, putting NULL in all database fields
 */
class WalletStorage extends ContentEntityDatabaseStorage implements WalletStorageInterface {

  /**
   * @see \Drupal\mcapi\WalletStorageInterface::getWalletIds()
   */
  function getWalletIds(EntityInterface $entity) {
    return db_select('mcapi_wallets', 'w')
      ->fields('w', array('wid'))
      ->condition('entity_type', $entity->entityType())
      ->condition('pid', $entity->id())
      ->execute()->fetchCol();
  }

  /**
   * @see \Drupal\mcapi\WalletStorageInterface::spare()
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
