<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\TransactionInterface.
 */

namespace Drupal\mcapi\Entity;

interface TransactionInterface extends \Drupal\Core\Entity\ContentEntityInterface {

  /**
   * load many transactions and key them by serial number instead of xid
   * @param mixed
   *   a serial number or array of serial numbers
   *
   * @return mixed
   *   an array of transaction entities or one transaction entity, depending on the input
   */
  static function loadBySerials($serials);

  /**
   * returns a clone of the transaction as an array with the children next to the parent
   *
   * @return Transaction[]
   *   transactions with the cloned parent transaction first and children property removed
   *
   */
  function flatten();

}