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

class TransitionManager extends DefaultPluginManager {

  private $config_factory;

  /**
   * Constructs the TransitionManager object
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   * @param LanguageManager $language_manager
   * @param ModuleHandlerInterface $module_Handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct('Plugin/Transition', $namespaces, $module_handler, '\Drupal\mcapi\Annotation\Transition');
    $this->setCacheBackend($cache_backend, $language_manager, 'transaction_transition');
    $this->config_factory = $config_factory;
  }

  function loadActive() {
    foreach ($this->getDefinitions() as $op => $definition) {
      $config = $this->config_factory->get('mcapi.transition.' . $definition['id']);
      //I hope the config is already translated by this point
      $plugins[$op] = $this->createInstance($op, $config->getRawData());
    }
    return $plugins;
  }

}