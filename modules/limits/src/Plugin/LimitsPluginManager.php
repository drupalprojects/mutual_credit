<?php

/**
 * @file
 */

namespace Drupal\mcapi_limits\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\mcapi\Entity\CurrencyInterface;

class LimitsPluginManager extends DefaultPluginManager {

  private $plugins;

  /**
   * Constructs the LimitsPluginManager object
   *
   * @param \Traversable $namespaces
   *
   * @param CacheBackendInterface $cache_backend
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $moduleHandler, CacheBackendInterface $cache_backend) {
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
   * get the plugin for the given currency
   *
   * @param CurrencyInterface $currency
   *
   * @param string $name
   *   the plugin to load, if not the currency's saved plugin.
   */
  function createInstanceCurrency(CurrencyInterface $currency, $plugin_id = NULL) {
    $curr_id = $currency->id();
    if (is_null($plugin_id)) {
      $settings = $currency->getThirdpartySettings('mcapi_limits');
      $plugin_id = isset($settings['plugin']) ? $settings['plugin'] : 'none';
    }
    return $this->createInstance(
      $plugin_id,
      ['currency' => $currency]//this should load the settings
    );
  }

}
