<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory as QueryFactoryBase;

/**
 * Query factory.
 */
class WalletQueryFactory extends QueryFactoryBase {

  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new \Drupal\mcapi\Entity\WalletQuery($entity_type, $conjunction, $this->connection, $this->namespaces);
  }

}
