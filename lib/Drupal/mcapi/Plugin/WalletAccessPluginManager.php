<?php

/**
 * @file
 * the plugin manager for the wallet access plugins
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

class WalletAccessPluginManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    parent::__construct('Plugin/WalletAccess', $namespaces, 'Drupal\mcapi\Annotation\WalletAccess');
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
