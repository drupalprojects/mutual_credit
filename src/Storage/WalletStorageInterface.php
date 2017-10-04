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

  /**
   * Get the wallets a user controls, which means holds, is burser of, or is the
   * entityOwner of the holder.
   *
   * @param int $uid
   *
   * @return int[]
   *   The wallet ids.
   *
   * @todo make this include the entity owners of the holders, but how?
   */
  public static function myWallets($uid);

}
