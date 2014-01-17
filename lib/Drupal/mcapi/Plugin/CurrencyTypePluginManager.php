<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

class CurrencyTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs the FieldTypePluginManager object
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager) {
    parent::__construct('Plugin/CurrencyType', $namespaces, 'Drupal\mcapi\Annotation\CurrencyType');

    $this->setCacheBackend($cache_backend, $language_manager, 'currency_type');
  }

  /**
   * Returns the default settings of a currency type.
   *
   * @param string $type
   *   A currency type name.
   *
   * @return array
   *   The widget type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type) {
    $info = $this->getDefinition($type);
    return isset($info['settings']) ? $info['settings'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $options += array(
      'configuration' => array(),
    );

    $plugin_id = $options['currency']->type;

    $configuration = $options['configuration'];

    $configuration+= array(
      'currency' => $options['currency'],
    );

    return $this->createInstance($plugin_id, $configuration);
  }
}