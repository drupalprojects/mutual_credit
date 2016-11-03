<?php

namespace Drupal\mcapi_exchanges\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
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
    // Use the transaction view, for the entity's collection route
    if ($transaction_list = $collection->get('view.mcapi_exchange_transactions.admin_collection')) {
      $collection->add('entity.mcapi_transaction.collection', $transaction_list);
      // Can't remove this because the view is creating a link for it.
      $collection->remove('view.mcapi_exchange_transactions.admin_collection');
    }

    // Change acess to the currency collection form
    $collection->get('entity.mcapi_currency.collection')
      ->setRequirements(['_user_is_logged_in' => 'true']);

    // Mass transaction forms should be available to permitted group members.
    $collection->get('mcapi.masspay')
      ->setRequirements(['_group_permission' => 'manage transactions']);
    $collection->get('mcapi.masspay.12many')
      ->setRequirements(['_group_permission' => 'manage transactions']);
  }


}
