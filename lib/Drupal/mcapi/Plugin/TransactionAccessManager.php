<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

class TransactionAccessManager extends DefaultPluginManager {

  /**
   * Constructs the TransactionAccessManager object
   * really I've got no idea what this function does
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   *   Dunno
   * @param LanguageManager $language_manager
   *   Dunno
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    parent::__construct('Plugin/TransactionAccess', $namespaces, 'Drupal\mcapi\Annotation\TransactionAccess');
    $this->setCacheBackend($cache_backend, $language_manager, 'transaction_access');
  }

  //public function getDefinitions();

  function viewsAccess(TransactionInterface $transaction) {

  }
}
