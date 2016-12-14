<?php

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Storage interface for wallet entity.
 */
interface WalletStorageInterface extends EntityStorageInterface {

  /**
   * Retrieve all the wallets held by a given ContentEntity.
   *
   * @param ContentEntityInterface $entity
   *   The entity.
   * @param bool $load
   *   TRUE means return the fully loaded wallets.
   *
   * @return \Drupal\mcapi\Entity\WalletInterface[]
   *   Or just the wallet ids if $load is FALSE.
   */
  public static function walletsOf(ContentEntityInterface $entity, $load = FALSE);
}
