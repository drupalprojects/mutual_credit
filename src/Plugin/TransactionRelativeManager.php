<?php

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\mcapi\Entity\TransactionInterface;

/**
 * Plugin manager for transaction relatives.
 */
class TransactionRelativeManager extends DefaultPluginManager {

  private $plugins = [];
  private $active = [];

  /**
   * Constructor.
   */
  public function __construct($namespaces, $cache_backend, $module_handler) {
    parent::__construct(
      'Plugin/TransactionRelative',
      $namespaces,
      $module_handler,
      '\Drupal\mcapi\Plugin\TransactionRelativeInterface',
      '\Drupal\mcapi\Annotation\TransactionRelative'
    );
    // don't know if this is important.
    $this->setCacheBackend($cache_backend, 'transaction_relatives');
  }

  /**
   * Activate the some or all Transaction relative plugins.
   *
   * @param array $names
   *   Names of plugins to activate. If empty, all will be activated.
   */
  public function activatePlugins($names = []) {
    $active = $names ?: array_keys($this->getDefinitions());
    $this->active = array_filter($active);
    return $this;
  }

  /**
   * Get the active transaction relative plugins.
   *
   * Plugins are deactivated to improve performance.
   *
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
      // Ensure anon and authenticated come first using alphabetical order!
    }
    ksort($plugins);
    return $plugins;
  }

  /**
   * Get the names of the config items.
   *
   * @return array
   *   The names of all the plugins, keyed by ID.
   */
  public function options() {
    foreach ($this->getDefinitions() as $id => $def) {
      // Is this translated?
      $names[$id] = $def['label'];
    }
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(TransactionInterface $transaction, array $plugin_names) {
    // Get the plugins.
    $user_ids = [];
    $plugin_ids = array_intersect_key($plugin_names, $this->getActivePlugins());
    foreach ($plugin_ids as $plugin_id) {
      $user_ids = array_merge($user_ids, $this->getActivePlugins()[$plugin_id]->getUsers($transaction));
    }
    return $user_ids;
  }

  /**
   * {@inheritdoc}
   */
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
