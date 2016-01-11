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
use Drupal\mcapi\Entity\TransactionInterface;

class TransactionRelativeManager extends DefaultPluginManager {

    private $plugins = [];
    private $active = [];

  public function __construct($namespaces, $cache_backend, $module_handler, $config_factory) {
    parent::__construct(
      'Plugin/TransactionRelative',
      $namespaces,
      $module_handler,
      '\Drupal\mcapi\Plugin\TransactionRelativeInterface',
      '\Drupal\mcapi\Annotation\TransactionRelative'
    );
    $this->setCacheBackend($cache_backend, 'transaction_relatives');//don't know if this is important
    $this->active = $config_factory->get('mcapi.settings')->get('active_relatives');
  }

  /*
   * retrieve the plugins which are not disabled in Config mcapi.settings
   *
   * @todo would a collection be the proper way to do this?
   */
  public function activePlugins() {
    if (empty($this->plugins) && $this->active) {
      foreach ($this->getDefinitions() as $id => $definition) {
        if (in_array($id, $this->active)) {
          $this->plugins[$id] = $this->createInstance($id);
        }
      }
      //hacky way to ensure anon and authenticated come first using alphabetical order!
      ksort($this->active);
    }
    return $this->plugins;
  }

  //return the names of the config items
  public function options($include_anon = FALSE) {
    foreach ($this->activePlugins() as $id => $info) {
      $names[$id] = $info->getPluginDefinition()['label'];//is this translated?
    }
    if (!$include_anon) {
      unset($names['anon']);
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
    $plugin_ids = array_intersect_key($plugin_names, $this->activePlugins());
    foreach ($plugin_ids as $plugin_id) {
      $user_ids = array_merge($user_ids, $this->activePlugins()[$plugin_id]->getUsers($transaction));
    }
    return $user_ids;
  }

}

