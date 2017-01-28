<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an exchange entity.
 *
 * @note This does NOT implement entityOwnerInterface because the owner is derived
 * from the holder, so you can't set the owner
 */
interface WalletInterface extends ContentEntityInterface {

  /**
   * Get the trading stats for all currencies allowed or used in the wallet.
   *
   * @return array
   *   Arrays of statistics, keyed by currency id.
   *
   * @see \Drupal\mcapi\Storage\TransactionStorageInterface::summaryData()
   */
  public function getSummaries();

  /**
   * Get the standard trading stats.
   *
   * @param string $curr_id
   *   the entity key for the mcapi_currency configEntity.
   *
   * @return array
   *   Array of statistics, keyed by stat name.
   *
   * @see $this->getStat()
   */
  public function getStats($curr_id);

  /**
   * Get the standard trading stats.
   *
   * @param string $curr_id
   *   The entity key for the mcapi_currency configEntity.
   * @param string $stat
   *   The for the actual statistic required, one of: balance, volume, trades,
   *   gross_in, gross_out.
   *
   * @return array or NULL
   *   Stat values keyed by stat name.
   */
  public function getStat($curr_id, $stat);


  /**
   * Get the balance for a particular currency
   *
   * @param string $curr_id
   *
   * @return string
   *   The formatted balance
   */
  public function balance($curr_id, $display = Currency::DISPLAY_NORMAL, $linked = TRUE);

  /**
   * Get the balance(s) of the current wallet, in worth format.
   *
   * @param string $stat
   *   Which stat we want to receive.
   *
   * @return array
   *   Worth values in no particular order, each with curr_id and (raw) value.
   */
  public function getStatAll($stat = 'balance');

  /**
   * Get all the transactions in a given period.
   *
   * @param int $from
   *   A unix timestamp.
   * @param int $to
   *   A unix timestamp.
   *
   * @return array
   *   All transactions between the times given.
   */
  public function history($from = 0, $to = 0);

  /**
   * Determine if the wallet is an intertrading wallet.
   *
   * @return bool
   *   TRUE if this is an intertrading wallet.
   */
  public function isIntertrading();

  /**
   * Returns the entity owner's user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The owner user entity.
   */
  public function getOwner();

  /**
   * Returns the entity owner's user ID.
   *
   * @return int|null
   *   The owner user ID, or NULL in case the user ID field has not been set on
   *   the entity.
   */
  public function getOwnerId();

  /**
   * Check whether a wallet has ever been used. i.e. whether the journal
   * references it.
   *
   * @return bool
   *   TRUE if the wallet has never been used.
   */
  public function isVirgin();

}
