<?php

/**
 * @file
 * Contains \Drupal\mcapi\listBuilder\TransactionListBuilder.
 */

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * build a listing of transactions.
 *
 * @ingroup entity_api
 */
class TransactionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {die('buildOperations');
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
      //same as parent but without caching.
      '#cache' => []
    );
    return $build;
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');
    $operations = \Drupal::entityTypeManager()
      ->getviewBuilder('mcapi_transaction')
      ->buildActionlinks($entity, TRUE);
    $operations += $this->moduleHandler()->invokeAll('entity_operation', array($entity));
    $this->moduleHandler->alter('entity_operation', $operations, $entity);
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }
  
}
