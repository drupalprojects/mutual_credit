<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an exchange entity.
 */
interface WalletInterface extends ContentEntityInterface {

  /**
   * Return the holding entity entity.
   *
   * @return ContentEntityInterface
   *   The one entity to which this wallet belongs.
   */
  public function getHolder();

  /**
   * Set the holder entity.
   *
   * @param ContentEntityInterface $entity
   *   The entity which is to hold the wallet.
   *
   * @return WalletInterface
   *   The one entity to which this wallet belongs.
   */
  public function setHolder(ContentEntityInterface $entity);

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
   * Determine whether this wallet was named automatically.
   *
   * @return string|NULL
   *   The name or FALSE if the wallet isn't autonamed.
   */
  public function autoName();

  /**
   * Check whether a wallet has ever been used. i.e. whether the journal
   * references it.
   *
   * @return bool
   *   TRUE if the wallet has never been used.
   */
  public function isVirgin();

}
