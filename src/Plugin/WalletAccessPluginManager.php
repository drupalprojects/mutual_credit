<?php

/**
 * @file
 * the plugin manager for the wallet access plugins
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

class WalletAccessPluginManager extends DefaultPluginManager {

  protected $currentUser;

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_Handler, AccountInterface $currentUser) {
    parent::__construct('Plugin/WalletAccess', $namespaces, $module_Handler, 'Drupal\mcapi\Annotation\WalletAccess');
    $this->setCacheBackend($cache_backend, $language_manager, 'wallet_access');
    $this->currentUser = $currentUser;
  }

  /**
   * this is used to make a nice listing
   */
  public function listDefinitions() {

  }

  function check($op, AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    return $this->factory->createInstance($plugin_id, $configuration, $this->currentUser);
  }

}
