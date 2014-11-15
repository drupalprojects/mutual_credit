<?php

/**
 * @file
 * Contains \Drupal\mcapi\Routing\RouteSubscriber.
 * Creates walletAdd routes for specified entities
 */

namespace Drupal\mcapi\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for walletadd routes on specified entities
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    //add a route to add a wallet to each entity type
    $types = \Drupal::config('mcapi.wallets')->get('entity_types');
    foreach((array)$types as $entity_type_bundle => $max) {
      list($entity_type, $bundle) = explode(':', $entity_type_bundle);
      $canonical = $this->manager->getDefinition($entity_type)->getLinkTemplate('canonical');
      if (!$entity_route = $collection->get($canonical)) {
        continue;
      }
      $path = $entity_route->getPath();
      $route = new Route("$path/addwallet");
      $route->setDefaults(array(
        '_form' => '\Drupal\mcapi\Form\WalletAddForm',
        '_title_callback' => '\Drupal\mcapi\Form\WalletAddForm::title'
      ));
      $route->setRequirements(array(
        '_custom_access' => '\Drupal\mcapi\Access\WalletAddAccessCheck::access'
      ));
      //see Plugin/Derivative/WalletLocalAction...
      $collection->add("mcapi.wallet.add.$entity_type", $route);
    }
    //I'm in two minds about whether it is appropriate to remove these routes
    //see hook_help
    //$collection->remove('field_ui.display_overview_mcapi_transaction');
    //$collection->remove('field_ui.display_overview_view_mode_mcapi_transaction');
    
  }

  /**
   * {@inheritdoc}
   * @todo is this needed?
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }
}
