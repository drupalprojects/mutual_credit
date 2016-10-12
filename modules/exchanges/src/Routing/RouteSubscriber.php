<?php

namespace Drupal\mcapi_exchanges\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Because the transaction collection is also the field ui base route, and
 * because views provides a superior listing to the entity's official
 * list_builder, this alters that view's route to the entity collection route.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $collection->get('user.register')->setPath('user/register/{group}');
    if ($transaction_list = $collection->get('view.mcapi_exchange_transactions.admin_collection')) {
      $collection->add('mcapi.transactions.collection', $transaction_list);
      // Can't remove this because the view is creating a link for it.
      $collection->remove('view.mcapi_exchange_transactions.admin_collection');
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
