<?php

/**
 * @file
 * Contains \Drupal\mcapi\ExtensibleEntityStorageControllerInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableEntityStorageControllerInterface;

interface TransactionStorageControllerInterface extends FieldableEntityStorageControllerInterface {

  //this doesn't override...
	//public function delete($delete_state);

  /**
   * Save Transaction Worth.
   *
   * @param Drupal\mcapi\TransactionInterface $transaction
   *  Transaction currently being saved.
   */
  public function saveWorths(TransactionInterface $transaction);

  //maintain the index table
  public function addIndex(TransactionInterface $transaction);
  public function indexRebuild();
  public function indexCheck();

  //filter by any field in the table; returns an array of serials keyed by xid
  public function filter(array $conditions, $offset, $limit);

  //returns an array of stats about a user's history in a currency
  public function summaryData(AccountInterface $account, CurrencyInterface $currency, array $filters);

  //when users are deleted, their uids should not persist in the transaction table.
  public function mergeAccounts($dest);
  public function count($currcode);
  public function volume($currcode);
  public function timesBalances(AccountInterface $account, CurrencyInterface $currency, $since);

}
