<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\TransactionInterface.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\EntityTypeInterface;

interface TransactionInterface extends ContentEntityInterface {

  /**
   * Validate a transaction, and generate the children by calling hook_transaction_children,
   * and validate the children
   * todo - I'm not sure this technically needs to be in the interface
   *
   * @throws McapiTransactionException
   *   when the parent transaction has errors
   *
   * @return array $messages
   *   a flat list of non-fatal exceptions from the parent and fatal exceptions in the child transactions
   */
  public function validateNew();

}