<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\WalletRouteProvider.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mcapi\Mcapi;


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
  public function  __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, $config, $entity_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->config = $config;
    parent::__construct($entity_manager, $entity_field_manager);//deprecated
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory')->get('mcapi.settings'),
      $container->get('entity.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = parent::getRoutes($entity_type);
    //this gives the canonical, the editForm and the deleteForm

    foreach(Mcapi::walletableBundles() as $entity_type_id => $bundles) {
      $canonical_path = $this->entityTypeManager
        ->getDefinition($entity_type_id, TRUE)
        ->getLinkTemplate('canonical');
      if ($canonical_path ) {
        //something funny going on with canonical path for user.page contains no entity_id
        $route = new Route("$canonical_path/addwallet");
        $route->setDefaults([
          '_entity_form' => 'mcapi_wallet.create',
          '_title_callback' => '\Drupal\mcapi\Form\WalletAddForm::title'
        ])
        ->setRequirement('_entity_create_access', 'mcapi_wallet')
        ->setOptions([
          //can't remember what this is for
          'parameters' => [
            'user' => [
              'type' => 'entity:user',
            ]
          ],
          '_route_enhancers' => [
            'route_enhancer.param_conversion', 'route_enhancer.entity'
          ]
        ]);
        $route_collection->add("mcapi.wallet.add.$entity_type_id", $route);
      }

      //route for viewing all wallets for one entity
      //@see \Drupal::config('mcapi.settings')->get('wallet_tab')
      $route = new Route("$canonical_path/wallets");
      $route->setDefaults([
        '_controller' => 'Drupal\mcapi\Controller\WalletController::entityWallets',
        '_title_callback' => 'Drupal\mcapi\Controller\WalletController::entityWalletsTitle'
      ])
      ->setRequirement('_custom_access', "\Drupal\mcapi\Access\EntityWalletsAccess::view")
      ->setOptions([
        'parameters' => [
          'entity' => [
            'type' => "entity:$entity_type_id",
          ]
        ]
      ]);
      $route_collection->add("entity.{$entity_type_id}.wallets", $route);

    }

    $route_collection
      ->get('entity.mcapi_wallet.edit_form')
      ->setDefault('_title', 'Manage wallet')
      ->setRequirement('user', '\d+');

    return $route_collection;
  }

}