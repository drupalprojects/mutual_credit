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

  /**
   *
   * @param type $namespaces
   * @param type $cache_backend
   * @param type $module_handler
   */
  public function __construct($namespaces, $cache_backend, $module_handler) {
    parent::__construct(
      'Plugin/TransactionRelative',
      $namespaces,
      $module_handler,
      '\Drupal\mcapi\Plugin\TransactionRelativeInterface',
      '\Drupal\mcapi\Annotation\TransactionRelative'
    );
    $this->setCacheBackend($cache_backend, 'transaction_relatives');//don't know if this is important
  }

  /**
   * sets this active
   * @param type $names
   */
  public function activatePlugins($names = []) {
    $active = $names ? : array_keys($this->getDefinitions());
    $this->active = array_filter($active);
    return $this;
  }

  /*
   * @todo work out how to use a Collection
   */
  public function getActivePlugins() {
    if (!$this->active) {
      $this->activatePlugins();
    }
    $plugins = [];
    foreach ($this->active as $id) {
      if (empty($this->plugins[$id])) {
        $this->plugins[$id] = $this->createInstance($id);
      }
      $plugins[$id] = $this->plugins[$id];
      //hacky way to ensure anon and authenticated come first using alphabetical order!
    }
    ksort($plugins);
    return $plugins;
  }

  //return the names of the config items
  public function options() {
    foreach ($this->getDefinitions() as $id => $def) {
       $names[$id] = $def['label'];//is this translated?
    }
    return $names;
  }

  /**
   * get the users who are related to a transaction
   * @param TransactionInterface $transaction
   * @param array $plugin_names
   * @return integer[]
   *   ids of user entities
   */
  public function getUsers(TransactionInterface $transaction, array $plugin_names) {
    //get the plugins
    $user_ids = [];
    $plugin_ids = array_intersect_key($plugin_names, $this->getActivePlugins());
    foreach ($plugin_ids as $plugin_id) {
      $user_ids = array_merge($user_ids, $this->getActivePlugins()[$plugin_id]->getUsers($transaction));
    }
    return $user_ids;
  }

  public function isRelative($transaction, $account) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    foreach ($this->getActivePlugins() as $plugin) {
      if ($plugin->isRelative($transaction, $account)) {
        return TRUE;
      }
    }
  }

}

