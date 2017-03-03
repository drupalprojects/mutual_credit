<?php

namespace Drupal\mcapi_forms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Subscriber to create a router item for each transaction form display.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add a route for each form.
    foreach (mcapi_form_displays_load() as $mode => $display) {
      if ($settings = $display->getThirdPartySetting('mcapi_forms', 'settings')) {
        if (!$settings['access_roles']) {
          throw new \Exception('mcapi_form settings has no access roles');
        }
        $route = new Route($settings['path']);
        $route->setDefaults([
          '_entity_form' => 'mcapi_transaction.' . $mode,
          '_title_callback' => '\Drupal\mcapi_forms\FirstPartyTransactionForm::title',
        ]);
        $route->setRequirements([
          '_role' => implode(', ', array_filter($settings['access_roles'])),
          '_entity_create_access' => 'mcapi_transaction'
        ]);
        $route->setOptions([
          'parameters' => [
            'mode' => $mode,
          ],
        ]);
        $collection->add('mcapi.1stparty.' . $mode, $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      RoutingEvents::ALTER => [
        ['onAlterRoutes', 0],
      ],
    ];
  }

}
