<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides routes for the user entity.
 */
class CurrencyRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {

    $route_collection = parent::getRoutes($entity_type);

    $route_collection->get('entity.mcapi_currency.edit_form')->setOption('_admin_route', TRUE);
    $route_collection->get('entity.mcapi_currency.delete_form')->setOption('_admin_route', TRUE);
    // Had some trouble installing once because this route wasn't available yet
    if ($route = $route_collection->get('entity.mcapi_currency.collection')) {
      $route->setDefault('_title', 'Currencies')
        ->setRequirement('_permission', 'manage mcapi')
        ->setOption('_admin_route', TRUE);
    }

    return $route_collection;
  }

}
