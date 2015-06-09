<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\TransactionRouteProvider.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for the user entity.
 */
class TransactionRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();

    $route = (new Route('/transaction/{mcapi_transaction}'))
      ->setDefaults([
        '_entity_view' => 'mcapi_transaction.full',
        '_title_callback' => 'Drupal\mcapi\Controller\TransactionController::pageTitle',
      ])
      ->setRequirement('_entity_access', 'mcapi_transaction.view')
      ->setOption('parameters', ['mcapi_transaction' => ['serial' => TRUE]]);
    $route_collection->add('entity.mcapi_transaction.canonical', $route);

    $route = (new Route('/transaction/log'))
      ->setDefaults([
        '_entity_form' => 'mcapi_transaction.admin',
        '_title' => 'Log transaction',
      ])
      ->setRequirement('_custom_access', 'Drupal\mcapi\Access\TransactionAccessControlHandler::enoughWallets')
      ->setRequirement('_permission', 'manage mcapi')
      ->setOption('_admin_route', TRUE);
    $route_collection->add('mcapi.transaction_log', $route);

    $route = (new Route('/transaction/{mcapi_transaction}/{transition}'))
      ->setDefaults([
        '_entity_form' => 'mcapi_transaction.transition',
      ])
      ->setRequirement('_entity_access', 'mcapi_transaction.transition')
      ->setOption('parameters', ['mcapi_transaction' => ['serial' => TRUE]]);
    $route_collection->add('mcapi.transaction.transition', $route);
    return $route_collection;
  }

}