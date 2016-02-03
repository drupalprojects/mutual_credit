<?php

/**
 * @file
 * Contains \Drupal\mcapi_forms\Routing\RouteSubscriber.
 * Creates walletAdd routes for specified entities
 * @deprecated
 */

namespace Drupal\mcapi_forms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

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
    //add a route for each form
    foreach (entity_load_multiple('firstparty_editform') as $id => $editform) {
      $route = new Route($editform->path);
      $route->setDefaults([
        '_entity_form' => 'mcapi_transaction.1stparty',
        '_title_callback' => '\Drupal\mcapi_forms\FirstPartyTransactionForm::title'
      ]);
      $route->setRequirements([
        '_user_is_logged_in' => 'TRUE',
        '_entity_create_access' => 'mcapi_transaction',
        '_custom_access' => '\Drupal\mcapi_forms\TransactionFormAccessCheck::access'
      ]);
      $route->setOptions([
        'parameters' => [
          'firstparty_editform' => ['id' => $id],
        ]
      ]);
      $collection->add('mcapi.1stparty.'.$id, $route);
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
