<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionRelativeManager.
 * Manages the plugins for the transaction relatives.
 *
 * @todo better understand collections and use one here.
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactory;

class TransactionRelativeManager extends DefaultPluginManager {

  private $active = [];

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct(
      'Plugin/TransactionRelative',
      $namespaces,
      $module_handler,
      '\Drupal\mcapi\Plugin\TransactionRelativeInterface',
      '\Drupal\mcapi\Annotation\TransactionRelative'
    );
    $this->setCacheBackend($cache_backend, 'transaction_relatives');//don't know if this is important
    $this->config = $config_factory->get('mcapi.misc')->get('active_relatives');
    $this->plugins = [];
  }

  /*
   * retrieve the plugins which are not disabled in Config mcapi.misc
   *
   * @todo would a collection be the proper way to do this?
   */
  public function active() {
    if (empty($this->active)) {
      foreach ($this->getDefinitions() as $id => $definition) {
        if (in_array($id, $this->config)) {
          $this->active[$id] = $this->createInstance($id);
        }
      }
    }
    return $this->active;
  }

  //return the names of the config items
  public function options() {
    foreach ($this->active() as $id => $info) {
      $names[$id] = $info->getPluginDefinition()['label'];//is this translated?
    }
    return $names;
  }


  /**
   *
   * @param array $plugin_names
   */
  public function getUsers(TransactionInterface $transaction, array $plugin_names) {
    //get the plugins
    $user_ids = [];
    $plugin_ids = array_intersect_key($plugin_names, $this->active());
    foreach ($plugin_ids as $plugin_id) {
      $user_ids = array_merge($user_ids, $this->active[$plugin_id]->getUsers($transaction));
    }
    return $user_ids;
  }

}

