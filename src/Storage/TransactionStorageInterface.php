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
   * Because this uses the index table, it knows nothing of transactions with state <  1
   * It might be a good idea to make a method specialised for retrieving balances only.
   * It would be an interesting SQL query which could get balances for multiple users.
   *
   * @param integer $wallet_id
   *
   * @param CurrencyInterface $currency
   *
   * @param array $filters
   *
   * @return array
   *   keys are trades, gross_in, gross_out, balance, volume, partners
   *
   * @see \Drupal\mcapi\Entity\WalletInterface::getSummaries()
   */
  function summaryData($wallet_id, array $filters);

  /**
   * count the number of transactions that meet the given conditions
   *
   * @param string $curr_id
   *
   * @param array $conditions
   *   keyed by transaction entity properties, values must match.
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   *
   * @param boolean $serial
   *   whether to count unique serial numbers or xids
   *
   * return integer
   */
  function count($curr_id = '', $conditions = [], $serial = FALSE);

  /**
   * get the total transaction volume of a currency.
   *
   * @param string $curr_id
   *
   * @param array $conditions
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   *
   * @return integer
   *   raw currency value
   */
  function volume($curr_id, $conditions = []);

  /**
   * Retrieve the full balance history
   * N.B if caching running balances remember to clear the cache whenever a transaction changes state or is deleted.
   *
   * @param Wallet $wallet
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
  function timesBalances(WalletInterface $wallet, $curr_id, $since);

  /**
   * Return the ids of all the wallets which HAVE USED this currency
   *
   * @param type $curr_id
   *
   * @param type $conditions
   */
  function wallets($curr_id, $conditions = []);

  /**
   * get all the balances for a given currency
   *
   * @param string $curr_id
   *
   * @return integer[]
   *   keyed by wallet id
   *
   */
  function balances($curr_id);


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
  function runningBalance($wid, $xid, $until, $sort_field);

}
