<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Field\WidgetPluginManager;
use Drupal\mcapi\Plugin\CurrencyTypePluginManager;
use Drupal\mcapi\CurrencyFieldDefinitions;

class CurrencyWidgetManager extends WidgetPluginManager {

  /*
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    // Fill in defaults for missing properties.
    $options += array(
      'configuration' => array(),
      'prepare' => TRUE,
    );

    $configuration = $options['configuration'];
    $currency = $options['currency'];
    $currency_type = $currency->getCurrencyType();

    // Fill in default configuration if needed.
    if ($options['prepare']) {
      $configuration = $this->prepareConfiguration($currency_type, $configuration);
    }

    $plugin_id = $configuration['type'];

    // Switch back to default widget if either:
    // - $type_info doesn't exist (the widget type is unknown),
    // - the field type is not allowed for the widget.
    $definition = $this->getDefinition($configuration['type']);
    if (!isset($definition['class']) || !(in_array('currency_type_' . $currency_type, $definition['field_types']))) {
      // Grab the default widget for the field type.
      $field_type_definition = $this->fieldTypeManager->getDefinition($currency_type);
      $plugin_id = $field_type_definition['default_widget'];
    }

    $configuration += array(
      'currency' => $currency,
      'field_definition' => new CurrencyFieldDefinitions(array('settings' => $currency->settings)),
    );
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($currency_type = NULL) {
    if (!isset($this->widgetOptions)) {
      $options = array();
      $widget_types = $this->getDefinitions();
      uasort($widget_types, 'drupal_sort_weight');
      foreach ($widget_types as $name => $widget_type) {
        foreach ($widget_type['field_types'] as $widget_field_type) {
          // Check that the field type exists.
          if (substr($widget_field_type, 0, 13) == 'currency_type') {
            $options[$widget_field_type][$name] = $widget_type['label'];
          }
        }
      }
      $this->widgetOptions = $options;
    }
    if (isset($currency_type)) {
      return (!empty($this->widgetOptions['currency_type_' . $currency_type]) ? $this->widgetOptions['currency_type_' . $currency_type] : array());
    }

    return $this->widgetOptions;
  }
}