<?php

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Interface for the transaction storage controller.
 */
interface TransactionStorageInterface extends EntityStorageInterface {

  /**
   * Truncate and rebuild the index table.
   */
  public function indexRebuild();

  /**
   * Check the integrity of the index table.
   *
   * @return bool
   *   TRUE if the table is integral.
   */
  public function indexCheck();

  /**
   * When a transaction is deleted, remove it from the index table.
   *
   * @param array $serials
   *   An array of serial numbers to drop from the index.
   */
  public function indexDrop(array $serials);

  /**
   * Get some gerneral purpose stats by adding up the transactions for a wallet.
   *
   * Because this uses the index table, it knows nothing of transactions with
   * state not 'done'. All this can be accomplished with views but this is one
   * handy method.
   *
   * @param string $curr_id
   *   The ID of a currency.
   * @param int $wallet_id
   *   The wid of a wallet.
   * @param array $filters
   *   Conditions keyed by property name suitable for getMcapiIndexQuery().
   *
   * @return array
   *   keys are trades, gross_in, gross_out, balance, volume, partners.
   *
   * @see \Drupal\mcapi\Entity\WalletInterface::getSummaries()
   *
   * @note This could be cached but remember it is possible to generate all kinds of stats, between any dates
   */
  public function walletSummary($curr_id, $wallet_id, array $filters);

  /**
   * Retrieve the full balance history.
   *
   * N.B if caching running balances remember to clear the cache whenever a
   * transaction changes state or is deleted.
   *
   * @param int $wallet_id
   *   The wid of a wallet.
   * @param string $curr_id
   *   The ID of a currency.
   * @param int $since
   *   A unixtime before which to exclude the transactions. Note that whole
   *   history needs to be loaded in order to calculate a running balance.
   *
   * @return array
   *   Balances keyed by timestamp.
   */
  public function historyOfWallet($wallet_id, $curr_id, $since);

  /**
   * Return the ids of all the wallets which HAVE USED this currency.
   *
   * @param string $curr_id
   *   The ID of a currency.
   * @param array $conditions
   *   Conditions keyed by property name suitable for getMcapiIndexQuery().
   */
  public function wallets($curr_id, $conditions = []);

  /**
   * Get the balance of a given wallet, up to a given transaction id.
   *
   * @param int $wid
   *   A wallet ID.
   * @param string $curr_id
   *   The ID of a currency.
   * @param int $until
   *   The meaning of this depends on the $sort_field.
   * @param string $sort_field
   *   The field by which it should be less than, and sort descending. Note this
   *   only works with fields in the base table coz its too complex for
   *   EntityQuery.
   *
   * @return int
   *   Raw currency value.
   */
  public function runningBalance($wid, $curr_id, $until, $sort_field = 'xid');

  /**
   * Get all the balances at the moment of the timestamp.
   *
   * Which means adding all transactions from the beginning until then
   * This provides a short of snapshot of the system.
   *
   * @param string $curr_id
   *   The ID of a currency.
   * @param array $conditions
   *   Conditions keyed by property name suitable for getMcapiIndexQuery().
   *
   * @return array
   *   Various stats, keyed by wallet id.
   */
  public function ledgerStateByWallet($curr_id, array $conditions);

  /**
   * Build a query on the transaction index table.
   *
   * @param string $curr_id
   *   The ID of a currency.
   * @param array $conditions
   *   Conditions keyed by property name suitable for getMcapiIndexQuery().
   *
   * @return Drupal\Core\Database\Query\Query
   *   An unexecuted db query.
   */
  public function ledgerStateQuery($curr_id, array $conditions);

  /**
   * Get a load of transaction stats per day, week, month or year.
   *
   * @param string $period
   *   Day, week, month, or year.
   * @param array $conditions
   *   Must contain at least a curr_id.
   *
   * @return array
   *   An array of dates, volumes trades, num wallets used in the periods
   *   preceding those dates.
   */
  public function historyPeriodic($curr_id, $period, $conditions);

  /**
   * Get all the currencies which the wallet has ever used.
   *
   * @param int $wid
   *   The wallet ID.
   */
  public function currenciesUsed($wid);

}
