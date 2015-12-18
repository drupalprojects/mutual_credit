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
   *   holder, a ContentEntity implementing EntityOwnerInterface
   *   intertrading, string only|include anything else will filter out intertrading wallets
   *
   * @param $boolean $offset
   *
   * @param $boolean $limit
   *
   * @return array
   *   The wallet ids
   */
  function filter(array $conditions, $offset = 0, $limit = NULL);

  /**
   *
   * @param array \Drupal\mcapi\Entity\Wallet[]
   *   keyed by wallet id
   */
   function reIndex(array $wallets);
}
