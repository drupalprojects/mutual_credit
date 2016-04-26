<?php

/**
 * @file
 * Contains \Drupal\mcapi_forms\Routing\RouteSubscriber.
 * Creates walletAdd routes for specified entities
 */

namespace Drupal\mcapi_forms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Subscriber to create a router item for each transaction form display
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    //add a route for each form
    foreach (mcapi_form_displays_load(TRUE) as $mode => $display) {
      $settings = $display->get('third_party_settings.mcapi_forms');
      $route = new Route($settings['path']);
      $route->setDefaults([
        '_entity_form' => 'mcapi_transaction.'.$mode,
        '_title_callback' => '\Drupal\mcapi_forms\FirstPartyTransactionForm::title'
      ]);
      $route->setRequirements([
        '_user_is_logged_in' => 'TRUE',
        '_entity_create_access' => 'mcapi_transaction',
        '_custom_access' => '\Drupal\mcapi_forms\TransactionFormAccessCheck::access'
      ]);
      $route->setOptions([
        'parameters' => [
          'mode' => $mode,
        ]
      ]);
      $collection->add('mcapi.1stparty.'.$mode, $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      RoutingEvents::ALTER => [
        ['onAlterRoutes', 0]
      ]
    ];
  }

}
