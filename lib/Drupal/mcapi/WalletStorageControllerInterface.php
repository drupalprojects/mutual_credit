<?php

/**
 * @file
 * Contains \Drupal\mcapi\WalletStorageControllerInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityInterface;

interface WalletStorageControllerInterface extends EntityStorageControllerInterface {

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
