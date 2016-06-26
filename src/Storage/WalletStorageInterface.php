<?php

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Storage interface for wallet entity.
 */
interface WalletStorageInterface extends EntityStorageInterface {

  /**
   * Get the wallets the given user can do the operation on.
   *
   * @param string $operation
   *   Can be payin or payout.
   * @param int $uid
   *   A user id.
   * @param string $match
   *   A string to match against the wallet name.
   *
   * @return integer[]
   *   The wallet IDs.
   */
  public function whichWalletsQuery($operation, $uid, $match = '');

}
