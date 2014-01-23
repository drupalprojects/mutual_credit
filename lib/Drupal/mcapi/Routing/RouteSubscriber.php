<?php

/**
 * @file
 * Contains \Drupal\mcapi\Routing\RouteSubscriber.
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
    foreach (\Drupal::config('mcapi.wallets')->get('types') as $entity_type) {
      $entity_info = $this->manager->getDefinition($entity_type);
      if (!$entity_route = $collection->get($entity_info['links']['canonical'])) {
        continue;
      }
      $path = $entity_route->getPath();
      //make a local action under the canonical link
      $route = new Route(
        "$path/addwallet",
        //"wallet/add/$entity_type",
        array(
          '_form' => '\Drupal\mcapi\Form\WalletAddForm',
        ),
        array(
          //'_entity_access' => 'mcapi_wallet.create'
          '_wallet_add_access' => 'TRUE'
        )
      );
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
