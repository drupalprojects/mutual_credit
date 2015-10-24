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
        '_title_callback' => 'Drupal\mcapi\Controller\CurrencyController::title',
      ])
      ->setRequirement('_permission', 'access content');
    $route_collection->add('entity.mcapi_currency.canonical', $route);

    $route = (new Route('/admin/accounting/currencies'))
      ->setDefaults([
        '_entity_list' => 'mcapi_currency',
        '_title' => 'Currencies',
      ])
      ->setRequirement('_permission', 'manage mcapi')
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.mcapi_currency.collection', $route);

    $route = (new Route('/admin/accounting/currencies/{mcapi_currency}'))
      ->setDefaults([
        '_entity_form' => 'mcapi_currency.edit',
        '_title' => 'Editing currency',
      ])
      ->setRequirement('_permission', 'configure mcapi')
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.mcapi_currency.edit_form', $route);

    $route = (new Route('/admin/accounting/currencies/add'))
      ->setDefaults([
        '_entity_form' => 'mcapi_currency.edit',
        '_title' => 'New currency',
      ])
      ->setRequirement('_entity_create_access', 'mcapi_currency')
      ->setOption('_admin_route', TRUE);
    $route_collection->add('mcapi.admin_currency_add', $route);

    $route = (new Route('/admin/accounting/currencies/{mcapi_currency}/delete'))
      ->setDefaults([
        '_entity_form' => 'mcapi_currency.delete',
        '_title' => 'Delete currency',
      ])
      ->setRequirement('_permission', 'configure mcapi')
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.mcapi_currency.delete_form', $route);

    return $route_collection;
  }
}
