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
  public function addIndex(TransactionInterface $transaction);

  public function indexRebuild();
  public function indexCheck();

  //there seems to be no delete() in this interface
}
