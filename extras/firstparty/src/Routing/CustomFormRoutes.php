<?php

/**
 * @file
 * Contains Drupal\mcapi_1stparty\Routing\CustomFormRoutes.
 * see services.yml
 */

namespace Drupal\mcapi_1stparty\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for 1stparty form routes.
 */
class CustomFormRoutes extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (entity_load_multiple('1stparty_editform') as $id => $editform) {
      if (empty($editform->path)) continue;//although right now 'path' is a required field
      $route = new Route(
        $editform->path,
        array(
          //see mcapi_1stparty_entity_type_alter
          '_entity_form' => 'mcapi_transaction.1stparty',
          '_title_callback' => '\Drupal\mcapi_1stparty\FirstPartyTransactionForm::title'),
        array(
          '_transaction_editform_access' => 'TRUE'
        ),
        array(
          'parameters' => array(
      	    'editform_id' => $id
          )
        )
      );
      $collection->add("mcapi.1stparty.$id", $route);
    }
  }

}