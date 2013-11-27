<?php

/**
 * @file
 */

namespace Drupal\mcapi\Plugin;

use Drupal\Core\Field\WidgetPluginManager;

class CurrencyWidgetManager extends WidgetPluginManager {

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
      return (!empty($this->widgetOptions['currency_type_' . $currency_type]) ? $this->widgetOptions['currency_type_' . $currency_type] : array()) + (!empty($this->widgetOptions['currency_type']) ? $this->widgetOptions['currency_type'] : array());
    }

    return $this->widgetOptions;
  }
}