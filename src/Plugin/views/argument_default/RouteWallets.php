<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\argument_default\RouteWallets.
 */

namespace Drupal\mcapi\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

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
   */
  public function getArgument() {
    $entityManager = \Drupal::EntityManager();
    //there's no validator in core either for ANY entity or for ANY contentEntity or ANY Owned Entity
    //only for ONE given specific entityType
    //so this function needs to decide whether to return an argument
    foreach (\Drupal::service('current_route_match')->getParameters()->all() as $key => $entity) {
      if (walletable($entity)) {
        $ids = Wallet::heldBy($entity);
      }
    }
    //@todo returning nothing means the view doesn't show - maybe throw a 404?
    //@see Drupal\mcapi_exchanges\Plugin\views\argument_default\RouteExchanges.

  }

}
