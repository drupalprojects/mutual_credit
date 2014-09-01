<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBuilder;
use Drupal\mcapi\Entity\State;

class TransitionManager extends DefaultPluginManager {

  private $config_factory;

  private $plugins;

  /**
   * Constructs the TransitionManager object
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   *
   * @param CacheBackendInterface $cache_backend
   *
   * @param LanguageManager $language_manager
   *
   * @param ModuleHandlerInterface $module_Handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct('Plugin/Transition', $namespaces, $module_handler, '\Drupal\mcapi\Annotation\Transition');
    //TODO Do we need to do anything to take advantage of the cache backend?
    $this->setCacheBackend($cache_backend, 'transaction_transitions');
    $this->config_factory = $config_factory;
    $this->plugins = array();
  }

  //TODO pluginbags would be better
  public function all() {
    foreach ($this->getDefinitions() as $id => $def) {
      $this->getPlugin($id);
    }
    return $this->plugins;
  }

  public function active(array $exclude = array()) {
    //static shouldn't be needed
    foreach ($this->all() as $id => $plugin) {
      if (!in_array($id, $exclude)) {
        if ($plugin->getConfiguration('status')) {
          $output[$plugin->getConfiguration('id')] = $plugin;
        }
      }
    }
    return $output;
  }

  public function getPlugin($id) {
    if (!array_key_exists($id, $this->plugins)) {
      $config = \Drupal::config('mcapi.transition.'. $id)->getRawData();
      $this->plugins[$id] = $this->createInstance($id, $config);
    }
    return $this->plugins[$id];
  }
}

