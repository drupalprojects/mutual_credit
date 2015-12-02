<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\WorthFieldItemList.
 * @todo make an interface for this?
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
  public function filterEmptyItems() {
    foreach ($this->list as $key => $item) {
      if (!$item->getValue()['value']) {
        unset($this->list[$key]);
      }
    }
    $this->list = array_values($this->list);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->list as $item) {
      if ($item->getValue()['value']) {
        return FALSE;
      }
    }
    return TRUE;
  }


  /**
   * {@inheritdoc}
   */
  public function __view($mode = 'normal') {
    foreach ($this->list as $item) {
      $renderable[] = $item->view($mode);
      $values[] = \Drupal::service('renderer')->render($renderable);
    }
    $delimiter = count($values) > 1 ?
      \Drupal::config('mcapi.settings')->get('worths_delimiter')
      :0;
    return ['#markup' => implode($delimiter, $values)];
  }


  /**
   * get the raw value for a given currency
   *
   * @param string $curr_id
   *
   * @return integer
   *   0 if the currency isn't known
   *
   */
  public function val($curr_id) {
    foreach ($this->list as $item) {
      if ($item->curr_id == $curr_id) {
        return $item->value;
      }
    }
    return 0;
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
    return render($this->view());
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

}
