<?php

/**
 * @file
 */

namespace Drupal\mcapi_limits\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

class LimitsPluginManager extends DefaultPluginManager {

  /**
   * Constructs the OperationManager object
   * really I've got no idea what this function does
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   *   Dunno
   * @param LanguageManager $language_manager
   *   Dunno
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    parent::__construct('Plugin/Limits', $namespaces, 'Drupal\mcapi_limits\Annotation\Limits');
    $this->setCacheBackend($cache_backend, $language_manager, 'mcapi_limits');
  }

}
