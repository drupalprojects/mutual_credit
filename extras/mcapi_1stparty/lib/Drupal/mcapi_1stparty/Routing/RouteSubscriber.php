<?php
/**
 * @file
 * Contains Drupal\mcapi_1stparty\Routing\RouteSubscriber.
 * see services.yml
 */


namespace Drupal\mcapi_1stparty\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;



/**
 * Subscriber for 1stparty form routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function routes(RouteCollection $collection) {
    die('Drupal\mcapi_1stparty\Routing\RouteSubscriber');//I don't know why this isn't firing, but do we need it?

    foreach (entity_load_multiple('1stparty_editform') as $id => $entity) {
      $params = array('payer' => 1, 'type' => 'blah');

      $route = new Route(
        "/transact/$id",
        array('_entity_form' => 'mcapi_transaction.1stparty'),
        array('_permission' => 'transact'),
        array('parameters' => $params)
      );
      $collection->add("mcapi.1stparty.$id", $route);
    }
  }

}