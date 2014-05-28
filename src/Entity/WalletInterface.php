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
   * @return array
   *   currencies, keyed by id
   */
  function currencies();

  /**
   * get a list of all the currencies in this wallet's scope
   * which is to say, in any of the wallet's parent's exchanges
   *
   * @return array
   *   currencies, keyed by id
   */
  function currencies_available();


  /**
   * Return some statistics held about this wallet's activity
   * @return array
   *   an array from \Drupal\mcapi\Storage\TransactionStorageInterface::summaryData()
   */
  function getStats($curr_id = NULL);


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

}
