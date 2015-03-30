<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityInterface;

interface WalletStorageInterface extends EntityStorageInterface {

  /**
   * get the wallets which belong to any entity
   *
   * @param ContentEntityInterface $entity
   *
   * @return array
   *   wallet ids
   *
   * @todo REPLACE this with $this->filter()
   */
  public static function getOwnedIds(ContentEntityInterface $entity);


}
