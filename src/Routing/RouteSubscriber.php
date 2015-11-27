<?php

/**
 * @file
 * Contains \Drupal\mcapi\Routing\RouteSubscriber.
 * Creates walletAdd routes for specified entities
 * @deprecated
 */

namespace Drupal\mcapi\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Subscriber for walletadd routes on specified entities
 */
class RouteSubscriber extends RouteSubscriberBase {

  private $settings;

  /**
   * @param Drupal\Core\Entity\EntityTypeManager $entity_manager
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  function __construct($entity_manager, $configFactory) {
    $this->entityTypeManager = $entity_manager;
    $this->settings = $configFactory->get('mcapi.settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    //add a route to add a wallet to each entity type
    
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    //$events[RoutingEvents::ALTER][] = ['onAlterRoutes', 1100];
    return [
      RoutingEvents::ALTER => [
        ['onAlterRoutes', -1100]
      ]
    ];
  }

}
