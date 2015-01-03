<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Plugin\views\argument_default\RouteExchanges.
 */

namespace Drupal\mcapi_exchanges\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Exchanges;

/**
 * return as a views argument, the exchanges the viewed entity is in
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
   */
  public function getArgument() {
    $entityManager = \Drupal::EntityManager();
    //there's no validator in core either for ANY entity or for ANY contentEntity or ANY Owned Entity
    //only for ONE given specific entityType
    //so this function needs to decide whether to return an argument
    foreach (\Drupal::service('current_route_match')->getParameters()->all() as $key => $entity) {
      if (Mcapi::walletable($entity)) {
        $ids = Exchanges::in($val);
      }
    }
    return implode('+', $ids);
    //returning nothing means the view doesn't show
  }

}
