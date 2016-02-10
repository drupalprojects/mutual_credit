<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\TransactionInterface.
 */

namespace Drupal\mcapi\Entity;

interface TransactionInterface extends \Drupal\Core\Entity\ContentEntityInterface {

  /**
   * load many transactions and key them by serial number instead of xid
   * @param integer
   *   a serial number
   *
   * @return \Drupal\mcapi\Entity\TransactionInterface
   */
  static function loadBySerial($serial);

  /**
   * returns a clone of the transaction as an array with the children next to the parent
   *
   * @return Transaction[]
   *   transactions with the cloned parent transaction first and children property removed
   *
   */
  function flatten();

}