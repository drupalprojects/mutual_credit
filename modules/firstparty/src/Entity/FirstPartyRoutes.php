<?php
/**
 * @file
 * Contains \Drupal\mcapi_1stparty\Entity\FirstPartyRoutes
 * Defines dynamic routes.
 */

namespace Drupal\mcapi_1stparty\Entity;

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
  public function getRoutes() {
    $route_collection = new RouteCollection();
    foreach (entity_load_multiple('firstparty_editform') as $id => $editform) {
      $route = new Route($editform->path);
      $route->setDefaults([
        '_entity_form' => 'mcapi_transaction.'.$id,
        '_title_callback' => '\Drupal\mcapi_1stparty\FirstPartyTransactionForm::title'
      ]);
      $route->setRequirements([
        '_user_is_logged_in' => 'TRUE',
        '_entity_create_access' => 'mcapi_transaction',
        '_custom_access' => '\Drupal\mcapi_1stparty\TransactionFormAccessCheck::access'
      ]);
      $route->setOptions([
        'parameters' => [
          'firstparty_editform' => ['id' => $id],
        ]
      ]);
      $route_collection->add('mcapi.1stparty.'.$id, $route);
    }
    return $route_collection;
  }
}
