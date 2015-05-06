<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\WalletRouteProvider.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for the user entity.
 */
class WalletRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();

    $route = (new Route('/wallet/{mcapi_wallet}'))
      ->setDefaults([
        '_entity_view' => 'mcapi_wallet.default',
        '_title_callback' => 'Drupal\mcapi\Controller\WalletController::pageTitle',
      ])
      ->setRequirement('_entity_access', 'mcapi_wallet.summary');
    $route_collection->add('entity.mcapi_wallet.canonical', $route);

    $route = (new Route('/wallet/{mcapi_wallet}/log'))
      ->setDefaults([
        '_controller' => 'Drupal\mcapi\Controller\WalletController::log',
        '_title_callback' => 'Drupal\mcapi\Controller\WalletController::pageTitle'
      ])
      ->setRequirement('_entity_access', 'mcapi_wallet.details');
    $route_collection->add('mcapi.wallet_log', $route);

    $route = (new Route('/wallet/{mcapi_wallet}/edit'))
      ->setDefaults([
        '_entity_form' => 'mcapi_wallet.edit',
        '_title' => 'Modify wallet'
      ])
      ->setRequirement('_entity_access', 'mcapi_wallet.edit');
    $route_collection->add('mcapi.wallet_edit', $route);

    return $route_collection;
  }
}
