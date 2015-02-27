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
   * @param ModuleHandlerInterface $module_handler
   *
   * @param ConfigFactory $config_factory
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct('Plugin/Transition', $namespaces, $module_handler, '\Drupal\mcapi\Plugin\TransitionInterface', '\Drupal\mcapi\Annotation\Transition');
    //TODO Do we need to do anything to take advantage of the cache backend?
    $this->setCacheBackend($cache_backend, 'transaction_transitions');
    $this->config_factory = $config_factory;
    $this->plugins = [];
  }

  //TODO pluginbags would be better
  public function all($editable = FALSE) {
    foreach ($this->getDefinitions() as $id => $def) {
      $this->getPlugin($id, $editable);
    }
    return $this->plugins;
  }

  public function active(array $exclude = [], $worth) {
    if ($worth) {
      $exclude = array_merge($exclude, $this->deletemodes($worth->currencies(TRUE)));
    }
    //static shouldn't be needed
    foreach ($this->all(FALSE) as $id => $plugin) {
      if (!in_array($id, $exclude) and $plugin->getConfiguration('status')) {
        $output[$plugin->getConfiguration('id')] = $plugin;
      }
    }
    return $output;
  }

  public function getPlugin($id, $editable = FALSE) {
    if (!array_key_exists($id, $this->plugins)) {
      $config = $this->config_factory->get('mcapi.transition.'. $id)->getRawData();
      $this->plugins[$id] = $this->createInstance($id, $config);
    }
    return $this->plugins[$id];
  }

  private function deletemodes(array $currencies) {
    $modes = array(
    	'1' => 'erase',
      '2' => 'delete'
    );
    foreach ($currencies as $currency) {
      $deletemodes[] = intval($currency->deletion);
    }
    $deletemode = min($deletemodes);
    //return everything larger than the min to be excluded
    return array_slice($modes, $deletemode);
  }
  
  //return the names of the config items
  public function getNames() {
    foreach ($this->getDefinitions() as $name => $info) {
      $names[] = 'mcapi.transition.'.$name;
    }
    return $names;
  }

}

