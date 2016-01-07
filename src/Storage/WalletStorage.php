<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorage.
 * 
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

class WalletStorage extends SqlContentEntityStorage {
  
  /**
   * 
   * @param array $values
   * @return type
   */
  protected function doCreate(array $values) {
    $entity = parent::doCreate($values);
    $entity->setHolder($values['holder']);
    return $entity;
  }
  
}
