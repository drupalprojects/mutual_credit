<?php

namespace Drupal\mcapi_exchanges\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for walletadd routes on specified entities.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (mcapi_exchanges_segregated()) {
      //also need to remove the tab that points to /user/register
      $collection->get('user.register')->setPath('user/register/{group}');
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
