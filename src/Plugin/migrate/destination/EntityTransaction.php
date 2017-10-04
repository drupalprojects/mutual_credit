<?php

namespace Drupal\mcapi\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

/**
 * @MigrateDestination(
 *   id = "entity:mcapi_transaction"
 * )
 */
class EntityTransaction extends EntityContentBase {

  /**
   * {@inheritdoc}
   *
   * Rollback the signatures as well as the entity
   */
  public function rollback(array $destination_identifier) {
    $xid = reset($destination_identifier);
    $transaction = $this->storage->load($xid);
    mcapi_signatures_mcapi_transaction_delete($transaction);
    parent::rollback($destination_identifier);
  }
}
