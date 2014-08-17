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
   * Perform a transition on the transaction
   *
   * @param string $transition_name
   *
   * @param array $values
   *   the form state values from the transition form
   *
   * @return array
   *   a renderable array
   */
  public function transition($transition_name, array $values);

  /**
   * load many transactions and key them by serial number instead of xid
   * @param mixed
   *   a serial number or array of serial numbers
   *
   * @return mixed
   *   an array of transaction entities or one transaction entity, depending on the input
   */
  public static function loadBySerials($serials);

  /**
   *
   * @throws mcapiTransactionException
   * @return Ambigous <void, multitype:unknown >
   */
  public function validate();

  /**
   * Work out which exchanges a transaction could be said to be in.
   *
   * @return \Drupal\mcapi\Entity\Exchange[]
   */
  public function exchanges();

}