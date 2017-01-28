<?php

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Represents a entity worth field.
 */
class WorthFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $empties = [];
    foreach ($this->list as $key => $item) {
      if (!$item->value && !$item->currency->zero) {
        $empties[$key] = $item->currcode;
      }
    }
    return count($empties) == count($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
    $this->filterEmptyItems($values);
  }

  /**
   * Get a list of currencies.
   *
   * @param bool $full
   *   True if the full entities are expected.
   *
   * @return array
   *   The currencies or Currency IDs
   */
  public function currencies($full = FALSE) {
    $c = [];
    foreach ($this->list as $item) {
      $c[$item->curr_id]  = $full ? $item->currency : $item->curr_id;
    }
    return $c;
  }

  /**
   * {@inheritdoc}
   */
  public function generateSampleItems($count = 1) {
    // We ignore the given $count, add one or maybe 2 currencies
    // use 1 or maybe two of the active currencies.
    $currencies = \Drupal::entityTypeManager()->getStorage('mcapi_currency')->loadByProperties(['status' => TRUE]);

    $field_definition = $this->getFieldDefinition();
    $field_type_class = \Drupal::service('plugin.manager.field.field_type')->getPluginClass($field_definition->getType());
    $temp = $currencies;
    shuffle($temp);
    $currency = array_pop($temp);
    $field_definition->currency = $currency;
    $values[] = $field_type_class::generateSampleValue($field_definition);
    if (rand(0, 4) == 0) {
      if ($field_definition->currency = array_pop($temp)) {
        $values[] = $field_type_class::generateSampleValue($field_definition);
      }
    }
    $this->setValue($values);
  }

  /**
   * {@inheritdoc}
   */
  public function __get($currency_name) {
    $val = 0;
    foreach ($this->list as $item) {
      if ($item->curr_id == $currency_name) {
        $val = $item->value;
      }
    }
    return $val;
  }

  public function __toString() {
    $vals = [];
    foreach ($this->list as $item) {
      $vals[] = (string)$item;
    }
    return implode(' | ', $vals);
  }

}
