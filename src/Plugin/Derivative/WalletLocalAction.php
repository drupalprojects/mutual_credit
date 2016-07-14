<?php

namespace Drupal\mcapi\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Local action definitions to create a wallet for each entity types configured.
 */
class WalletLocalAction extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Mutual credit module miscellaneous settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * {@inheritdoc}
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
    // Add wallet links can go in three locations.
    foreach ($this->settings->get('entity_types') as $entity_type_bundle => $max) {
      if (!$max) {
        continue;
      }
      list($entity_type, $bundle_name) = explode(':', $entity_type_bundle);
      $key = "mcapi.wallet.add.{$bundle_name}.action";
      // Assumes bundle names don't clash!
      $this->derivatives[$key] = [
        'id' => "mcapi.wallet.add." . $entity_type . '.action',
      // Taken from the routesubscriber.
        'route_name' => "mcapi.wallet.add.$entity_type",
        'title' => t('Add Wallet'),
        // Assumes this pattern for the canonical route name
        // otherwise we have to derive it somehow, and I'm not sure how.
        'appears_on' => ['entity.' . $entity_type . '.canonical'],
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
