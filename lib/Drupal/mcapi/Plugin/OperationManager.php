<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

class OperationManager extends DefaultPluginManager {

  /**
   * Constructs the TransActionPluginManager object
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    parent::__construct('Plugin/Operation', $namespaces, 'Drupal\mcapi\Annotation\Operation');
    $this->setCacheBackend($cache_backend, $language_manager, 'transaction_operation');
  }

  /**
   * Returns the default settings of a transaction_operation
   *
   * @param string $type
   *   A TransAction type name.
   *
   * @return array
   *   The widget type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type) {
    $info = $this->getDefinition($type);
    return isset($info['settings']) ? $info['settings'] : array();
  }


  public function getDefinitionsVisible() {
    $definitions = parent::getDefinitions();
    //we might want to filter these
    return $definitions;
  }

}
