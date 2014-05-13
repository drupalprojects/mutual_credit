<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

interface WalletStorageInterface extends FieldableEntityStorageInterface {

  /**
   * get the wallets which belong to any entity
   * @param EntityInterface $entity
   * @return array
   *   wallet ids
   */
  public function getWalletIds(EntityInterface $entity);

  /**
   * check if the max number of wallets has been reached for that entity
   * @param EntityInterface $owner
   * @return boolean
   *   TRUE if the limit has not been reached
   */
  public function spare(EntityInterface $owner);

}
