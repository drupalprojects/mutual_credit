<?php

/**
 * @file
 */

namespace Drupal\mcapi_limits\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

class LimitsPluginManager extends DefaultPluginManager {

  /**
   * Constructs the TransitionManager object
   * really I've got no idea what this function does
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   *   Dunno
   * @param ModuleHandlerInterface $module_handler
   *   Dunno
   * @param LanguageManager $language_manager
   *   Dunno
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Limits', $namespaces, $module_handler, 'Drupal\mcapi_limits\Annotation\Limits');
    $this->setCacheBackend($cache_backend, $language_manager, 'mcapi_limits');
  }
}
