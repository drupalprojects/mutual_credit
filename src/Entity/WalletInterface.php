<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\WalletInterface.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an exchange entity.
 */
interface WalletInterface extends ContentEntityInterface {


  /**
   * return the parent entity if there is one, otherwise return the wallet itself
   * @return ContentEntityInterface
   *   The one entity to which this wallet belongs
   */
  public function getOwner();


  /**
   * get the exchanges which this wallet can be used in.
   * @return array
   *   exchange entities, keyed by id
   */
  public function in_exchanges();


  /**
   * get a list of the currencies held in the wallet
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   */
  function currencies_used();

  /**
   * get a list of all the currencies currently in this wallet's scope
   * which is to say, in any of the wallet's parent's exchanges
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   */
  function currencies_available();

  /**
   * get a list of the currencies used or available to this wallet
   * @return CurrencyInterface[]
   *   keyed by currency id
   */
  function currencies_all();

  /**
   * get the trading stats for all currencies used so far
   * @return array
   * @see $this->getStat()
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
   * @param integer $from
   *   a unix timestamp
   * @param integer $to
   *   a unix timestamp
   * @return array
   *   all transactions between the times given
   */
  public function history($from = 0, $to = 0);

  /**
   * Handle the deletion of the wallet's parent
   * If the wallet has no transactions it can be deleted
   * Otherwise make the passed exchange the parent, must be found.
   *
   * @param ExchangeInterface $exchange
   */
  public function orphan(ExchangeInterface $exchange = NULL);
}
