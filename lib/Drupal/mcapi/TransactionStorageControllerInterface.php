<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionStorageControllerInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableEntityStorageControllerInterface;
use Drupal\Core\Entity\EntityInterface;

interface TransactionStorageControllerInterface extends FieldableEntityStorageControllerInterface {

  /**
   * {inheritdoc}
   */
  public function delete(array $transactions);

  /**
   *  write 2 rows to the transaction index table, one for the payee, one for the payer
   *  @param TransactionInterface $transaction
   */
  public function addIndex(TransactionInterface $transaction);

  /**
   * truncate and rebuild the index table
   */
  public function indexRebuild();

  /**
   * Check the integrity of the index table
   *
   * @return boolean
   *   TRUE if the table is integral
   */
  public function indexCheck();

  /**
   * When a transaction is deleted, remove it from the index table
   * @param integer $serial
   */
  public function indexDrop($serial);

  /**
   * Populates the top level transaction with the next unused serial number
   * @param TransactionInterface $transaction
   *
   * @return integer
   */
  public function nextSerial(TransactionInterface $transaction);

  /**
   * Filter by any field in the table; returns an array of serials keyed by xid
   * this is especially needed should views not be available, but is used in any case.
   *
   * @param array $conditions
   *   keyed by transaction entity properties, values must match.
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   * @param number $offset
   * @param number $limit
   *
   * @return array
   *   an array keyed by xid with serial numbers as values
   */
  public function filter(array $conditions, $offset = 0, $limit = 25);

  /**
   * Get some gerneral purpose stats by adding up the transactions for a given wallet
   * This could be cached but remember it is possible to generate all kinds of stats, between any dates
   * Because this uses the index table, it knows nothing of transactions with state <  1
   * It might be a good idea to make a method specialised for retrieving balances only.
   * It would be an interesting SQL query which could get balances for multiple users.
   *
   * @param integer $wallet_id
   * @param CurrencyInterface $currency
   * @param array $filters
   *
   * @return array
   *   keyed by property or empty if there were no transactions
   */
  public function summaryData($wallet_id, array $filters);

  /**
   * count the number of transactions that meet the given conditions
   *
   * @param string $currcode
   * @param array $conditions
   *   keyed by transaction entity properties, values must match.
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   * @param boolean $serial
   *   whether to count unique serial numbers or xids
   *
   * return integer
   */
  public function count($currcode = '', $conditions = array(), $serial = FALSE);

  /**
   * get the total transaction volume of a currency
   *
   * @param unknown $currcode
   * @param array $conditions
   *   keyed by transaction entity properties, values must match.
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   * @return integerstring
   *   ready for formatting with $currency->format
   */
  public function volume($currcode, $conditions = array());

  /**
   * Retrieve the full balance history
   * N.B if caching running balances remember to clear the cache whenever a transaction changes state or is deleted.
   *
   * @param integer $wallet_id
   * @param string $currcode
   * @param integer $since
   *   A unixtime before which to exclude the transactions.
   *   Note that all transactions will have to be loaded in order to calculate the first shown balance
   *
   * @return array
   *   keyed by timestamp and balance from that moment
   */
  public function timesBalances($wallet_id, $currcode, $since);


  //public function saveWorths(TransactionInterface $transaction);
}
