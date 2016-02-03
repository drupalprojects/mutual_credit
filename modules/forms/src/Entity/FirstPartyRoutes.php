<?php
/**
 * @file
 * Contains \Drupal\mcapi_forms\Entity\FirstPartyRoutes
 * Defines dynamic routes.
 */

namespace Drupal\mcapi_forms\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for 1stparty form routes.
 * Add a route for every designed 1stparty form
 */
class FirstPartyRoutes extends \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider{

  /**
   *
   * @return RouteCollection
   *
   * @see mcapi_forms_entity_type_alter which declares new _entity_forms
   * @todo this should contain the routes in the yml file, while these routes should be in the routeSubscriber
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);

    //rather a lot of changes from the defaults...
    $route_collection->get('entity.firstparty_editform.edit_form')
      //->setDefault('_title', 'Transaction form designer')
      ->setRequirements(['_permission' => 'design 1stparty forms'])
      ->setOption('_admin_route', TRUE);

    $route_collection->get('entity.firstparty_editform.delete_form')
      ->setRequirements(['_permission' => 'design 1stparty forms'])
      ->setOption('_admin_route', TRUE);

    //create 1stparty form
    $route = new Route('/admin/accounting/forms/create');
    $route->setDefaults([
      '_entity_form' => 'firstparty_editform.add',
      '_title' => 'Create payment form'
    ]);
    $route->setRequirement('_permission', 'design 1stparty forms');
    $route->setOption('_admin_route', TRUE);
    $route_collection->add('mcapi.admin_firstparty_editform.add', $route);

    //confirm enable
    $route = new Route('/admin/accounting/forms/{firstparty_editform}/enable');
    $route->setDefaults([
      '_entity_form' => 'firstparty_editform.enable',
      '_title' => 'Enable payment form'
    ]);
    $route->setRequirement('_permission', 'design 1stparty forms');
    $route->setOption('_admin_route', TRUE);
    $route_collection->add('entity.firstparty_editform.enable', $route);

    //confirm disable
    $route = new Route('/admin/accounting/forms/{firstparty_editform}/disable');
    $route->setDefaults([
      '_entity_form' => 'firstparty_editform.disable',
      '_title' => 'Disable payment form'
    ]);
    $route->setRequirement('_permission', 'design 1stparty forms');
    $route->setOption('_admin_route', TRUE);
    $route_collection->add('entity.firstparty_editform.disable', $route);

    return $route_collection;
  }
}

/*
 *

entity.firstparty_editform.delete_form:
  path: '/admin/accounting/forms/{firstparty_editform}/delete'
  defaults:
    _entity_form: 'firstparty_editform.delete'
  requirements:
    _permission: 'design 1stparty forms'
  options:
    _admin_route: TRUE

*/