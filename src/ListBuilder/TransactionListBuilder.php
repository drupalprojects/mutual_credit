<?php

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Build a listing of transactions.
 *
 * @ingroup entity_api
 */
class TransactionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
      // Same as parent but without caching.
      '#cache' => [],
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = \Drupal::entityTypeManager()
      ->getviewBuilder('mcapi_transaction')
      ->buildActionlinks($entity);
    return $operations;
  }

}
