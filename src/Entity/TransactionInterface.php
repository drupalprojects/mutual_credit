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

}