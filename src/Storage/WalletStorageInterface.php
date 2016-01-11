<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\mcapi\Entity\TransactionInterface;

interface WalletStorageInterface extends EntityStorageInterface {

  /**
   * get the wallets the given user can do the operation on
   *
   * @param string $operation
   *   can be payin or payout
   *
   * @param integer $uid
   *   a user id
   *
   * @param string $match
   *   a user id
   *
   * @return integer[]
   *   the wallet ids
   *
   */
  public function whichWalletsQuery($operation, $uid, $match = '');

}
