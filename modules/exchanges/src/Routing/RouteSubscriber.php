<?php

namespace Drupal\mcapi_exchanges\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Because the transaction collection is also the field ui base route, and
 * because views provides a superior listing to the entity's official
 * list_builder, this alters that view's route to comply with the entity.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (mcapi_exchanges_segregated()) {
      $collection->get('user.register')->setPath('user/register/{group}');
    }
    
    $transaction_list = $collection->get('view.mcapi_exchange_transactions.admin');
    $collection->add('mcapi.transactions.collection', $transaction_list);
    $collection->remove('mcapi.mcapi_exchange_transactions.admin');

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
