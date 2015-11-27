<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\WalletRouteProvider.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides routes for the wallet entity.
 */
class WalletRouteProvider extends \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider {

      /**
   * Constructs a new DefaultHtmlRouteProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function  __construct(EntityManagerInterface $entity_type_manager, $config, $entity_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityManager = $entity_manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')->get('mcapi.settings'),
      $container->get('entity.manager')//deprecated
    );
  }

    
  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);
    //this gives the canonical and the deleteForm
    //@todo inject this somehow?
    foreach($this->config->get('entity_types') as $entity_type_bundle => $max) {
      if ($max) {
        //route for adding a new wallet, per entity type
        list($entity_type_id, $bundle_name) = explode(':', $entity_type_bundle);
        $canonical_path = $this->entityTypeManager
          ->getDefinition($entity_type_id, TRUE)
          ->getLinkTemplate('canonical');
        if ($canonical_path) {
          //something funny going on with canonical path for user.page contains no entity_id
          $route = new Route("$canonical_path/addwallet");
          $route->setDefaults([
            '_entity_form' => 'mcapi_wallet.create',
            '_title_callback' => '\Drupal\mcapi\Form\WalletAddForm::title'
          ])
          ->setRequirement('_entity_create_access', 'mcapi_wallet:mcapi_wallet')
          ->setOptions([
            'parameters' => [
              'user' => [
                'type' => 'entity:user',
              ]
            ],
            '_route_enhancers' => [
              'route_enhancer.param_conversion', 'route_enhancer.entity'
            ]
          ]);
          $route_collection->add("mcapi.wallet.add.$bundle_name", $route);
          
          //route for viewing all wallets for one entity
          //@see \Drupal::config('mcapi.settings')->get('wallet_tab')
          $route = new Route("$canonical_path/wallets");
          $route->setDefaults([
            '_controller' => 'Drupal\mcapi\Controller\WalletController::entityWallets',
            '_title_callback' => 'Drupal\mcapi\Controller\WalletController::entityWalletsTitle'
          ])
          ->setRequirement('_entity_access', 'mcapi_wallet.details')
          ->setOptions([
            'parameters' => [
              'entity' => [
                'type' => 'entity:$entity_type_id',
              ]
            ],
            '_route_enhancers' => [
              'route_enhancer.param_conversion', 'route_enhancer.entity'
            ]
          ]);
          $route_collection->add("entity.{$bundle_name}.wallets", $route);
        }
      }
    }
    
    $route_collection
      ->get('entity.mcapi_wallet.edit_form')->setDefault('_title', 'Manage wallet');
    
    
    return $route_collection;
  }

}