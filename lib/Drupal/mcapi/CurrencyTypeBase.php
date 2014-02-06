<?php

/**
 * @file
 *  Contains Drupal\mcapi\CurrencyTypeBase.
 */

namespace Drupal\mcapi;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for Currency Types for default methods.
 */
abstract class CurrencyTypeBase extends PluginBase implements CurrencyTypeInterface {

  private $plugin_id;

  /**
   * Currency type desinitions
   *
   * @var array
   */
  private $settings;

  /**
   * Whether default settings have been merged into the current $settings.
   *
   * @var bool
   */
  protected $defaultSettingsMerged = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $configuration += array(
      'settings' => array(),
    );
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = $configuration['settings'];
  }

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::getSettings().
   */
  public function getSettings() {
    // Merge defaults before returning the array.
    if (!$this->defaultSettingsMerged) {
      $this->mergeDefaults();
    }
    return $this->settings;
  }

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::getSetting().
   */
  public function getSetting($key) {
    // Merge defaults if we have no value for the key.
    if (!$this->defaultSettingsMerged && !array_key_exists($key, $this->settings)) {
      $this->mergeDefaults();
    }
    return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
  }

  /**
   * Merges default settings values into $settings.
   */
  protected function mergeDefaults() {
    $this->settings += $this->getDefaultSettings();
    $this->defaultSettingsMerged = TRUE;
  }

  /**
   * Implements Drupal\field\Plugin\PluginSettingsInterface::getDefaultSettings().
   */
  public function getDefaultSettings() {
    $definition = $this->getPluginDefinition();
    return $definition['settings'];
  }
}
