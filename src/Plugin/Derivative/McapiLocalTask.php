<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Derivative\McapiLocalTask.
 * @deprecated ?
 */

namespace Drupal\mcapi\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides local task definitions to create a wallet tab for all entities of types configured.
 */
class McapiLocalTask extends DeriverBase implements ContainerDeriverInterface {
  
  use StringTranslationTrait;

  var $settings;
  var $derivatives = [];

  /**
   * {@inheritDoc}
   */
  public function __construct($settings, $string_translation) {
    $this->settings = $settings;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('config.factory')->get('mcapi.settings'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach($this->settings->get('entity_types') as $entity_type_bundle => $max) {
      if (!$max) {
        continue;
      }
      continue;
      
      list($entity_type_id, $bundle_name) = explode(':', $entity_type_bundle);
      $key = "mcapi.wallet.{$bundle_name}.task";
      //assumes bundle names don't clash!
      $this->derivatives[$key] = [
        'id' => $key,
        'route_name' => "entity.{$bundle_name}.wallets",
        'title' => $this->formatPlural($max, 'Wallet', 'Wallets'),
        //assumes this pattern for the canonical route name
        'base_route' => "entity.{$bundle_name}.canonical"
      ];
    }
    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }
  
}
