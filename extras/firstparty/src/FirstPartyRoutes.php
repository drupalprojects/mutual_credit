<?php
/**
 * @file
 * Contains \Drupal\mcapi_1stparty\FirstPartyRoutes
 * Defines dynamic routes.
 */

namespace Drupal\mcapi_1stparty;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for 1stparty form routes.
 * Add a route for every designed 1stparty form
 */
class FirstPartyRoutes {

  /**
   *
   * @return RouteCollection
   *
   * @see mcapi_1stparty_entity_type_alter which declares new _entity_forms
   */
  public function routes() {
    $route_collection = new RouteCollection();
    foreach (entity_load_multiple('1stparty_editform') as $id => $editform) {
      $route = new Route($editform->path);
      $route->setDefaults([
        '_controller' => '\Drupal\mcapi_1stparty\FirstPartyTransactionForm::loadForm',
        '_title_callback' => '\Drupal\mcapi_1stparty\FirstPartyTransactionForm::title'
      ]);
      $route->setRequirements([
        '_custom_access' => '\Drupal\mcapi_1stparty\TransactionFormAccessCheck::access',
        '_permission' => 'design 1stparty forms'
      ]);
      $route->setOptions([
        'parameters' => [
          '1stparty_editform' => $id,
        ]
      ]);
      $route_collection->add('mcapi.1stparty.'.$id, $route);
    }
    return $route_collection;
  }
}