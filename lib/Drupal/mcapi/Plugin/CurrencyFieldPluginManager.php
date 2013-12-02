<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;

class CurrencyFieldPluginManager extends FieldTypePluginManager {

  private $currencyManager;
  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler, CurrencyTypePluginManager $currencyManager) {
    parent::__construct($namespaces, $cache_backend, $language_manager, $module_handler);

    $this->currencyManager = $currencyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    // We are not caching this as it will be cached by the currency manager.
    $this->definitions = $this->currencyManager->getDefinitions();
  }

}