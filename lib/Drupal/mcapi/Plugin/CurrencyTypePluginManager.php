<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

class CurrencyTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs the FieldTypePluginManager object
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    parent::__construct('Plugin/CurrencyType', $namespaces, 'Drupal\mcapi\Annotation\CurrencyType');

    $this->setCacheBackend($cache_backend, $language_manager, 'currency_type');
  }

}