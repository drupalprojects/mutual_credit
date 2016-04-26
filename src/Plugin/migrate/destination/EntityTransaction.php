<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\migrate\destination\EntityTransaction.
 * @deprecated?
 */

namespace Drupal\mcapi\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:mcapi_transaction"
 * )
 */
class EntityTransaction extends EntityContentBase {


  /**
   * {@inheritdoc}
   */
  public function ___import(Row $row, array $old_destination_id_values = array()) {

  }

  /**
   * {@inheritdoc}
   */
  protected function processStubRow(Row $row) {
    parent::processStubRow($row);

  }

}
