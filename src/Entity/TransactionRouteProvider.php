<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\TransactionRouteProvider.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the transaction entity.
 */
class TransactionRouteProvider extends \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);
    //adjust the routes to load with the serial number
    $options = $route_collection
      ->get('entity.mcapi_transaction.canonical')->getOptions();
    $options['parameters']['mcapi_transaction']['serial'] = TRUE;

    $route_collection
      ->get('entity.mcapi_transaction.add_form')
      ->setRequirement('_permission', 'create 3rdparty transactions');

    $route_collection
      ->get('entity.mcapi_transaction.canonical')
      ->setRequirement('user', '\d+')
      ->setOptions($options);

    $route_collection
      ->get('entity.mcapi_transaction.edit_form')
      ->setRequirement('user', '\d+')
      ->setOptions($options);

    //one route to cover all the transaction operations.
    $route = (new Route('/transaction/{mcapi_transaction}/{operation}'))
      ->setDefaults([
        '_entity_form' => 'mcapi_transaction.operation',
      ])
      ->setRequirement('_entity_access', 'mcapi_transaction.operation')
      ->setRequirement('user', '\d+')
      ->setOptions($options);
    $route_collection->add('mcapi.transaction.operation', $route);

    return $route_collection;
  }

}
