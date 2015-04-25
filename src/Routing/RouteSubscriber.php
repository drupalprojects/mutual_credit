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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subscriber for walletadd routes on specified entities
 */
class RouteSubscriber extends RouteSubscriberBase {


  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    //add a route to add a wallet to each entity type
    foreach (SELF::walletOwningEntityRoutes() as $canonicalRouteName => $bundle) {
      $canonical_path = $collection->get($canonicalRouteName)->getPath();
      //TODO find out how this is supposed to work after beta 9
      if ($canonical_path == '/user'){
        $canonical_path = '/user/{user}';
      }
      if ($canonical_path == '/node'){
        $canonical_path = '/node/{node}';
      }
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
      $collection->add("mcapi.wallet.add.$bundle", $route);
    }
    //would be nice to delete these but local actions depend on them
//    $collection->remove('entity.entity_view_display.mcapi_transaction.default');    
//    $collection->remove('entity.entity_view_display.$bundle_entity_type.view_mode');
  }

  /**
   * {@inheritdoc}
   * @todo is this needed?
   */
  public static function __getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }
  
  
  /**
   * 
   * @return string[]
   * 
   * @todo update this after beta7 with the proper canonical routes directly
   */
  public static function walletOwningEntityRoutes() {
    $config = \Drupal::configFactory()->get('mcapi.wallets');
    $routes = [];
    foreach($config->get('entity_types') as $entity_type_bundle => $max) {
      if ($max) {
        list($entity_type, $bundle_name) = explode(':', $entity_type_bundle);
        $canonical = \Drupal::entityManager()
          ->getDefinition($entity_type, TRUE)
          ->getLinkTemplate('canonical');
        if (!$canonical) {
          continue;
        }
        if ($canonical == "/user/{user}") {
          $canonical = 'entity.user.canonical';//user.page';
        }
        elseif ($canonical == '/node/{node}') {
          $canonical = 'entity.node.canonical';
        }
        $routes[$canonical] = $bundle_name;
      }
    }
    return $routes;
  }
  
}
