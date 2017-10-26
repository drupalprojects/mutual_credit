<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory as QueryFactoryBase;

/**
 * Query factory.
 */
class TransactionQueryFactory extends QueryFactoryBase {

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new TransactionQuery($entity_type, $conjunction, $this->connection, $this->namespaces);
  }

}
