<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

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

    // @todo probably remove this https://www.drupal.org/node/2770845
    $route = (new Route('/admin/accounting/currencies'))
      ->setDefaults([
        '_entity_list' => 'mcapi_currency',
        '_title' => 'Currencies',
      ])
      ->setRequirement('_permission', 'manage mcapi')
      ->setOption('_admin_route', TRUE);
    $route_collection->add('entity.mcapi_currency.collection', $route);

    return $route_collection;
  }

}
