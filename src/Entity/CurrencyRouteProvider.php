<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\CurrencyRouteProvider.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for the user entity.
 */
class CurrencyRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();

    $route = (new Route('/currency/{mcapi_currency}'))
      ->setDefaults([
        '_entity_view' => 'mcapi_currency.default',
        //@todo for some reason the $currency isn't being passed to this function
        //'_title_callback' => 'Drupal\mcapi\Controller\CurrencyController::title',
      ])
      ->setRequirement('_permission', 'access content');
    $route_collection->add('entity.mcapi_currency.canonical', $route);

    return $route_collection;
  }

}