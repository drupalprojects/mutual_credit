<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Routing\RouteSubscriber.
 * Changes access to routes according to segregate_exchanges setting
 */

namespace Drupal\mcapi_exchanges\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for walletadd routes on specified entities
 */
class RouteSubscriber extends RouteSubscriberBase {

  private $segregate_exchanges;

  /**
   * @see mcapi_exchanges.services.yml
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  function __construct($configFactory) {
    $this->segregate_exchanges = $configFactory->get('mcapi_exchanges.settings')->get('segregate_exchanges');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->segregate_exchanges) {
      $collection->get('user.register')->setRequirements([]);
      $collection->get('entity.mcapi_exchange.canonical')->setRequirements(['_access' => 'TRUE']);
    }
    else {
      $collection->get('entity.mcapi_exchange.join')->setRequirements([]);
    }

    if (\Drupal::moduleHandler()->moduleExists('contact')) {
      $route = new Route(
        "/exchange/{mcapi_exchange}/contact",
        [
          '_entity_form' => 'mcapi_exchange.contact',
          '_title' => 'Contact exchange',
        ],
        ['_permission' => 'ccess site-wide contact form']
      );
      $collection->add("entity.mcapi_exchange.contact", $route);
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
