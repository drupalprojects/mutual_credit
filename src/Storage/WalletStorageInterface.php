<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\mcapi\TransactionInterface;

interface WalletStorageInterface extends EntityStorageInterface {

  /**
   * get the wallets the given user can do the operation on
   * 
   * @param string $operation
   *
   * @param AccountInterface $account
   *
   * @return integer[]
   *   wallet ids
   */
  function walletsUserCanActOn($operation, $account);

  /**
   * get a selection of wallets, according to $conditions
   *
   * @param array $conditions
   *   options are:
   *   entity_types, an array of entitytypeIds
   *   array exchanges, an array of exchange->id()s
   *   string fragment, part of the name of the wallet or parent entity
   *   wids, wallet->id()s to restrict the results to
   *   owner, a ContentEntity of a type which according to wallet settings, could have children
   *   intertrading, a boolean indicating whether to include or exclude _intertrading wallets
   *
   * @param $boolean $offset
   *
   * @param $boolean $limit
   *
   * @param boolean $intertrading
   *   TRUE if the '_intertrading' wallets should be included.
   *
   * @return integer[]
   *   wallet ids
   */
  static function filter(array $conditions, $offset = 0, $limit = NULL);

}
