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

class WalletAccessPluginManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_Handler) {
    parent::__construct('Plugin/WalletAccess', $namespaces, $module_Handler, 'Drupal\mcapi\Annotation\WalletAccess');
    $this->setCacheBackend($cache_backend, $language_manager, 'wallet_access');
  }

  /**
   * this is used to make a nice listing
   */
  public function listDefinitions() {

  }

  function check($op, AccountInterface $account) {
    return TRUE;
  }


}
