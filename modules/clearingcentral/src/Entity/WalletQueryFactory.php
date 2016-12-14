<?php

namespace Drupal\mcapi_cc\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory as QueryFactoryBase;

/**
 * Query factory.
 *
 * This does nothing but it tells the system to look next door for the Query.
 */
class WalletQueryFactory extends QueryFactoryBase {

  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new \Drupal\mcapi\Entity\WalletQuery($entity_type, $conjunction, $this->connection, $this->namespaces);
  }
}
