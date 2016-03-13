<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\WorthFieldItemList.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Represents a configurable entity worth field.
 */
class WorthFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $empties= [];
    foreach ($this->list as $key => $item) {
      if (!$item->value && !$item->currency->zero) {
        $empties[$key] = $item->currcode;
      }
    }
    return count($empties) == count($this->list);
  }


  /**
   * {@inheritdoc}
   * @todo Revisit the need when all entity types are converted to NG entities.
   */
  public function getValue($include_computed = FALSE) {
    $values = array();
    foreach ($this->list as $delta => $item) {
      $val = $item->value;
      if (strlen($val)) {
        $values[$delta] = $item->getValue();
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);
    $this->filterEmptyItems($values);
  }

  public function currencies($full = FALSE) {
    $c = [];
    foreach ($this->list as $item) {
      if ($full) {
        $c[$item->curr_id] = $item->currency;
      }
      else {
        $c[] = $item->curr_id;
      }
    }
    return $c;
  }

  public function __toString() {
    foreach ($this->list as $item) {
      $worths[] = (string)$item;
    }
    return implode(\Drupal::config('mcapi.settings')->get('worths_delimiter'), $worths);
  }

  /**
   * {@inheritdoc}
   */
  public function generateSampleItems($count = 1) {
    //we ignore the given $count, add one or maybe 2 currencies
    //use 1 or maybe two of the active currencies
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
}
