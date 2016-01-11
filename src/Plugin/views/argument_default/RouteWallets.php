<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\argument_default\RouteWallets.
 */

namespace Drupal\mcapi\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\mcapi\Mcapi;

/**
 * The fixed argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "route_wallets",
 *   title = @Translation("Wallets from route entity ")
 * )
 */
class RouteWallets extends ArgumentDefaultPluginBase {

  /**
   * Return the default argument.
   * @todo inject the service
   */
  public function getArgument() {
    //there's no validator in core either for ANY entity or for ANY contentEntity or ANY Owned Entity
    //only for ONE given specific entityType
    //so this function needs to decide whether to return an argument
    $ids = [];
    foreach (\Drupal::routeMatch()->getParameters()->all() as $key => $entity) {
      if (Mcapi::maxWalletsOfBundle($entity->getEntityTypeId(), $entity->bundle())) {
        $wids = Mcapi::walletsOf($entity);
      }
    }
    //@todo returning nothing means the view doesn't show - maybe throw a 404?
    //@see Drupal\mcapi_exchanges\Plugin\views\argument_default\RouteExchanges.
    return implode('+', $wids);

  }

}
