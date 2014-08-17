<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\TransactionStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\mcapi\Entity\TransactionInterface;

interface TransactionStorageInterface extends FieldableEntityStorageInterface {

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
   * @param array $serials
   */
  public function indexDrop($serials);

  /**
   * Filter by any field in the table; returns an array of serials keyed by xid
   * this is especially needed should views not be available, but is used in any case.
   *
   * @param array $conditions
   *   keyed by transaction entity properties, values must match.
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   *   note that 'value' refers to the raw value of the worth field.
   *   Also note 'including' and 'involving' conditions will filter inclusively and
   *   exclusively respectively on the wallet ids passed. Actually I think those are the same
   *   because wallets can ONLY trade with other wallets in the same exchange, unless they have moved
   * @param number $offset
   * @param number $limit
   *
   * @return array
   *   an array keyed by xid with serial numbers as values
   */
  public static function filter(array $conditions = array(), $offset = 0, $limit = 25);

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
   *   keys are trades, gross_in, gross_out, balance, volume, partners
   *
   * @see \Drupal\mcapi\Entity\WalletInterface::getSummaries()
   */
  public function summaryData($wallet_id, array $filters);

  /**
   * count the number of transactions that meet the given conditions
   *
   * @param integer $curr_id
   * @param array $conditions
   *   keyed by transaction entity properties, values must match.
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   * @param boolean $serial
   *   whether to count unique serial numbers or xids
   *
   * return integer
   */
  public function count($curr_id = '', $conditions = array(), $serial = FALSE);

  /**
   * get the total transaction volume of a currency
   *
   * @param integer $curr_id
   * @param array $conditions
   *   keyed by transaction entity properties, values must match.
   *   Except in the case of state. If state is NULL, no filter will be applied,
   *   if state is not in the $conditions, a filter for positive states will be added.
   * @return integerstring
   *   ready for formatting with $currency->format
   */
  public function volume($curr_id, $conditions = array());

  /**
   * Retrieve the full balance history
   * N.B if caching running balances remember to clear the cache whenever a transaction changes state or is deleted.
   *
   * @param integer $wallet_id
   * @param integer $curr_id
   * @param integer $since
   *   A unixtime before which to exclude the transactions.
   *   Note that whole history needs to be loaded in order to calculate a running balance
   *
   * @return array
   *   Balances keyed by timestamp
   */
  public function timesBalances($wallet_id, $curr_id, $since);


}
