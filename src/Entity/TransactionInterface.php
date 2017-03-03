<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for Transaction entity.
 */
interface TransactionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  
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
