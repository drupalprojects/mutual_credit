<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Derivative\McapiLocalTask.
 * @deprecated
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

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
