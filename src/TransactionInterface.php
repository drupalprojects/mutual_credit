<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\EntityTypeInterface;

interface TransactionInterface extends ContentEntityInterface {

  /**
   * Validate a transaction, and generate the children by calling hook_transaction_children,
   * and validate the children
   * This function calls itself!
   * Adds exceptions to each transaction's exception array
   * Does NOT throw any errors
   *
   * @return array $messages
   *   a flat list of non-fatal messages from all transactions in the cluster
   */
  public function validate();

}