<?php

namespace Drupal\mcapi_exchanges\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\mcapi\Mcapi;

/**
 * Return as a views argument, the exchanges the viewed entity is in.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "route_exchanges",
 *   title = @Translation("The exchanges the viewed entity is in")
 * )
 */
class RouteExchanges extends ArgumentDefaultPluginBase {

  /**
   * Return the default argument.
   *
   * @todo inject the Routematch service
   */
  public function getArgument() {
    // there's no validator in core either for ANY entity or for ANY contentEntity or ANY Owned Entity
    // only for ONE given specific entityType
    // so this function needs to decide whether to return an argument.
    $ids = [];
    foreach (\Drupal::routeMatch()->getParameters()->all() as $entity) {
      if (Mcapi::maxWalletsOfBundle($entity->getEntityTypeId(), $entity->bundle())) {
        $ids = Exchanges::memberOf($entity);
        break;
      }
    }
    return implode('+', $ids);
    // Returning nothing means the view doesn't show.
  }

}
