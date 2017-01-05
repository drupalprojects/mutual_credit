<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Transaction entity.
 */
interface TransactionInterface extends ContentEntityInterface {

  /**
   * Load many transactions and key them by serial number instead of xid.
   *
   * This is usually needed when taking the transaction id from the url,
   * otherwise, entityQuery works with the xids, same as other entities.
   *
   * @param int $serial
   *   A serial number.
   *
   * @return \Drupal\mcapi\Entity\TransactionInterface
   *   The fully loaded transaction with children.
   */
  public static function loadBySerial($serial);

  /**
   * Get transactions as a flat array, i.e. without children.
   *
   * @return Transaction[]
   *   Transactions with the cloned parent first, and children property removed
   */
  public function flatten();

  /**
   * Add children to the transaction or make other changes.
   *
   * @note this must be called explicitly, and PRIOR to validation.
   */
  public function assemble();

  /**
   * Returns the time that the transaction was created.
   *
   * @return int
   *   The timestamp of when the transaction was created.
   */
  public function getCreatedTime();

}
