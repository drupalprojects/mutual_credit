<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\WalletInterface.
 */

namespace Drupal\mcapi\Entity;

use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an exchange entity.
 */
interface WalletInterface extends ContentEntityInterface {

  /**
   * return the holding entity entity
   *
   * @return ContentEntityInterface
   *   The one entity to which this wallet belongs
   */
  function getHolder();

  /**
   * set the holder entity
   * @param
   *
   * @return WalletInterface
   *   The one entity to which this wallet belongs
   */
  function setHolder(ContentEntityInterface $entity);

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
   * determine if the wallet is an intertrading wallet
   *
   * @return boolean
   */
  function isIntertrading();

  /**
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   *
   */
  public function currenciesAvailable();

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
}
