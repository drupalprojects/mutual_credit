<?php

namespace Drupal\mcapi_limits\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\mcapi\Entity\CurrencyInterface;

/**
 * Plugin manager for Wallet limits.
 */
class LimitsPluginManager extends DefaultPluginManager {

  private $plugins;

  /**
   * Constructs the LimitsPluginManager object.
   */
  public function __construct($namespaces, $moduleHandler, $cache_backend) {
    parent::__construct(
      'Plugin/Limits',
      $namespaces,
      $moduleHandler,
      '\Drupal\mcapi_limits\Plugin\McapiLimitsInterface',
      '\Drupal\mcapi_limits\Annotation\Limits'
    );
    $this->setCacheBackend($cache_backend, 'mcapi_limits');
  }

  /**
   * Get the plugin for the given currency.
   *
   * @param CurrencyInterface $currency
   *   The current currency.
   * @param string $plugin_id
   *   The plugin to load, if not the currency's saved plugin.
   */
  public function createInstanceCurrency(CurrencyInterface $currency, $plugin_id = NULL) {
    if (is_null($plugin_id)) {
      $settings = $currency->getThirdpartySettings('mcapi_limits');
      $plugin_id = isset($settings['plugin']) ? $settings['plugin'] : 'none';
    }
    return $this->createInstance(
      $plugin_id,
      // This should load the settings.
      ['currency' => $currency]
    );
  }

}
