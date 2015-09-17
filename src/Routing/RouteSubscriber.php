<?php

/**
 * @file
 * Contains \Drupal\mcapi\Routing\RouteSubscriber.
 * Creates walletAdd routes for specified entities
 */

namespace Drupal\mcapi\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Subscriber for walletadd routes on specified entities
 */
class RouteSubscriber extends RouteSubscriberBase {

  private $settings;

  /**
   * @param Drupal\Core\Entity\EntityManager $entity_manager
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  function __construct($entity_manager, $configFactory) {
    $this->entityManager = $entity_manager;
    $this->settings = $configFactory->get('mcapi.settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    //add a route to add a wallet to each entity type
    foreach($this->settings->get('entity_types') as $entity_type_bundle => $max) {
      if ($max) {
        list($entity_type, $bundle_name) = explode(':', $entity_type_bundle);
        $canonical_path = $this->entityManager
          ->getDefinition($entity_type, TRUE)
          ->getLinkTemplate('canonical');
        if ($canonical_path) {
          //something funny going on with canonical path for user.page contains no entity_id
          $route = new Route("$canonical_path/addwallet");
          $route->setDefaults([
            '_form' => '\Drupal\mcapi\Form\WalletAddForm',
            '_title_callback' => '\Drupal\mcapi\Form\WalletAddForm::title'
          ]);
          $route->setRequirements([
            '_entity_create_access' => 'mcapi_wallet:mcapi_wallet'
          ]);
          //see Plugin/Derivative/WalletLocalAction...
          $collection->add("mcapi.wallet.add.$bundle_name", $route);
        }
      }
    }
    
  }

  /**
   * @return string[]
   *   bundle names keyed by canonical path
   */
  public static function walletOwningEntityRoutes() {
    $routes = [];
    foreach($this->walletConfig->get('entity_types') as $entity_type_bundle => $max) {
      if ($max) {
        list($entity_type, $bundle_name) = explode(':', $entity_type_bundle);
        $canonical_path = $this->entityManager
          ->getDefinition($entity_type, TRUE)
          ->getLinkTemplate('canonical');
        if ($canonical_path) {
          $routes[$canonical_path] = $bundle_name;
        }
      }
    }
    return $routes;
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = ['onAlterRoutes', -1100];
    return $events;
  }

}
