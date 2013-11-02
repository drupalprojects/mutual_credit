<?php

/**
 * @file
 * Contains \Drupal\mcapi\ExtensibleEntityStorageControllerInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableEntityStorageControllerInterface;

interface TransactionStorageControllerInterface extends FieldableEntityStorageControllerInterface {

  /**
   * Save Transaction Worth.
   *
   * @param Drupal\mcapi\TransactionInterface $transaction
   *  Transaction currently being saved.
   */
  public function saveWorths(TransactionInterface $transaction);

}