<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\TransactionStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\mcapi\Entity\WalletInterface;

interface TransactionStorageInterface extends EntityStorageInterface {

  /**
   * truncate and rebuild the index table
   */
  function indexRebuild();

  /**
   * Check the integrity of the index table
   *
   * @return boolean
   *   TRUE if the table is integral
   */
  function indexCheck();

  /**
   * When a transaction is deleted, remove it from the index table
   *
   * @param array $serials
   */
  function indexDrop(array $serials);


  /**
   * Get some gerneral purpose stats by adding up the transactions for a given wallet
   * This could be cached but remember it is possible to generate all kinds of stats, between any dates
   * Because this uses the index table, it knows nothing of transactions with state not 'done'
   * All this can be accomplished with views but this is one handy method
   *
   * @param string $curr_id
   *
   * @param integer $wallet_id
   *
   * @param array $filters
   *
   * @return array
   *   keys are trades, gross_in, gross_out, balance, volume, partners
   *
   * @see \Drupal\mcapi\Entity\WalletInterface::getSummaries()
   */
  function walletSummary($curr_id, $wallet_id, array $filters);

  /**
   * Retrieve the full balance history
   * N.B if caching running balances remember to clear the cache whenever a transaction changes state or is deleted.
   *
   * @param int $wallet_id
   *
   * @param string $curr_id
   *
   * @param integer $since
   *   A unixtime before which to exclude the transactions.
   *   Note that whole history needs to be loaded in order to calculate a running balance
   *
   * @return array
   *   Balances keyed by timestamp
   */
  function historyOfWallet($wallet_id, $curr_id, $since);

  /**
   * Return the ids of all the wallets which HAVE USED this currency
   *
   * @param type $curr_id
   *
   * @param type $conditions
   */
  function wallets($curr_id, $conditions = []);

  /**
   * get the balance of a given wallet, up to a given transaction id, for a
   * given currency
   *
   * @param integer $wid
   *
   * @param integer $xid
   *
   * @param integer $until
   *   the meaning of this depends on the $sort_field
   *
   * @param string $sort_field
   *   the field by which it should be less than, and sort descending. Note this
   *   only works with fields in the base table coz its too complex for EntityQuery
   *
   * @return integer raw currency value
   *
   */
  function runningBalance($wid, $curr_id, $until, $sort_field = 'xid');

  /**
   * get all the balances at the moment of the timestamp,
   * which means adding all transactions from the beginning until then
   * This provides a short of snapshot of the system
   *
   * @param string $curr_id
   * @param array $conditions
   * @return various stats, keyed by wallet id
   */
  function ledgerStateByWallet($curr_id, array $conditions);

  /**
   * get all the balances at the moment of the timestamp,
   * which means adding all transactions from the beginning until then
   * This provides a short of snapshot of the system
   * @param string $curr_id
   * @param array $conditions
   * @return Query
   */
  function ledgerStateQuery($curr_id, array $conditions);

  /**
   *
   * @param string $period
   *   day, week, month, or year
   * @param array $conditions
   *   must contain at least a curr_id
   *
   * @return array
   *   an array of dates, volumes trades, num wallets used in the periods preceding those dates
   */
  function historyPeriodic($curr_id, $period, $conditions);

  /**
   * Get all the currencies which the wallet has ever used.
   */
  function currenciesUsed($wid);
}
