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
  protected function alterRoutes(RouteCollection $collection, $provider) {
    $types = \Drupal::config('mcapi.wallets')->get('entity_types');
    foreach((array)$types as $entity_type_bundle => $max) {
      list($entity_type, $bundle) = explode(':', $entity_type_bundle);
      $canonical = $this->manager->getDefinition($entity_type)->getLinkTemplate('canonical');
      if (!$entity_route = $collection->get($canonical)) {
        continue;
      }
      $path = $entity_route->getPath();
      $route = new Route(
        "$path/addwallet",
        array(
          '_form' => '\Drupal\mcapi\Form\WalletAddForm',
        ),
        array(
          '_wallet_add_access' => 'TRUE'
        )
      );
      //see Plugin/Derivative/WalletLocalAction...
      $collection->add("mcapi.wallet.add.$entity_type", $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }
}
