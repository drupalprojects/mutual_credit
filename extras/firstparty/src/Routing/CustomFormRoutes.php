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
  protected function alterRoutes(RouteCollection $collection, $provider) {
    //@todo without this line the alter function runs once for each module and attempts to add the same routes multiple times.
    //however in mcapi\RouteSubscriber this doesn't happen. What's the difference?
    if ($provider != 'mcapi_1stparty') return;
    foreach (entity_load_multiple('1stparty_editform') as $id => $entity) {
      $route = new Route(
        $entity->path,
        array(
          '_entity_form' => 'mcapi_transaction.1stparty',
          '_title_callback' => '\Drupal\mcapi_1stparty\Form\FirstPartyTransactionForm::title'),
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