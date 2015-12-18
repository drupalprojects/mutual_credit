<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\WalletInterface.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an exchange entity.
 */
interface WalletInterface extends ContentEntityInterface {

  /**
   * get the user who is responsible for this wallet, that means either the
   * user-holder, or the owner of the holder. This deliberately echos the EntityOwnerInterface
   */
  function getOwner();

  /**
   * return the parent entity if there is one, otherwise return the wallet itself
   * 
   * @return ContentEntityInterface
   *   The one entity to which this wallet belongs
   */
  function getHolder();

  /**
   * change the holder of the current wallet
   */
  function setHolder(EntityOwnerInterface $entity);

  /**
   * get a list of the currencies held in the wallet
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   */
  function currenciesUsed();

  /**
   * get the trading stats for all currencies allowed or used in the wallet
   *
   * @return array
   *
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::summaryData().
   */
  function getSummaries();

  /**
   * get the standard trading stats
   * @param string $curr_id
   *   the entity key for the mcapi_currency configEntity
   * @return array or NULL if the wallet hasn't used the currency
   * @see $this->getStat()
   */
  function getStats($curr_id);

  /**
   * get the standard trading stats
   *
   * @param string $curr_id
   *   the entity key for the mcapi_currency configEntity
   *
   * @param string $stat
   *   the for the actual statistic required, one of:
   *   balance, volume, trades, gross_in, gross_out
   *
   * @return array or NULL if the wallet hasn't used the currency
   */
  function getStat($curr_id, $stat);


  /**
   * get all the transactions in a given period
   *
   * @param integer $from
   *   a unix timestamp
   *
   * @param integer $to
   *   a unix timestamp
   *
   * @return array
   *   all transactions between the times given
   */
  function history($from = 0, $to = 0);

  /**
   * Handle the deletion of the wallet's parent
   * If the wallet has no transactions it can be deleted
   * Otherwise make the passed exchange the parent, must be found.
   *
   * @param ContentEntityInterface $holder
   */
  static function orphan(ContentEntityInterface $holder);

  /**
   * get the ids of the wallets owned by the given entity
   *
   * @param WalletInterface $entity
   *
   * @return array
   *   wallet ids belonging to the passed entity
   */
  static function heldBy(WalletInterface $entity);

  /**
   * determine if the wallet is an intertrading wallet
   * 
   * @return boolean
   */
  function isIntertrading();

}
