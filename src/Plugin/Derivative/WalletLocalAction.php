<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Derivative\WalletLocalAction.
 */

namespace Drupal\mcapi\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions to create a wallet for all entities of types configured.
 */
class WalletLocalAction extends DeriverBase implements ContainerDeriverInterface {

  var $settings;

  var $derivatives = [];

  /**
   * {@inheritDoc}
   *
   */
  public function __construct($settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('config.factory')->get('mcapi.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    
    //add wallet links can go in three locations
    foreach($this->settings->get('entity_types') as $entity_type_bundle => $max) {
      if (!$max) {
        continue;
        }
      list($entity_type, $bundle_name) = explode(':', $entity_type_bundle);
      $key = "mcapi.wallet.add.{$bundle_name}.action";
      //assumes bundle names don't clash!
      $this->derivatives[$key] = [
        'id' => "mcapi.wallet.add.".$bundle_name.'.action',
        'route_name' => "mcapi.wallet.add.$bundle_name",//taken from the routesubscriber
        'title' => t('Add Wallet'),
        //assumes this pattern for the canonical route name
        //otherwise we have to derive it somehow, and I'm not sure how.
        'appears_on' => ['entity.'.$entity_type.'.canonical']
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }
}
