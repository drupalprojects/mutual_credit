<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\WorthFieldItemList.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Represents a configurable entity worth field.
 */
class WorthFieldItemList extends FieldItemList {

  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    //instead of
    //parent::__construct($definition, $name, $parent);
    //which creates an empty first list item, which would mean we have to them unset it thus
    //unset($this->list[0]);
    //lets just copy the the lines from the __construct which would have been inherited
    $this->definition = $definition;
    $this->parent = $parent;
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function first() {
    echo "WorthFieldItemList:first should never be called.";
    mtrace();
  }

  /**
   * We have to override this because the default behaviour is to create
   * the list with an empty item keyed 0, then to set the value of that default item
   * Because we are using the key as the currency id we can't create a worth item without it
   */
  public function __set($property_name, $value) {
    list($key, $quant) = each($value);
    $this->list[$key] = $this->createItem($key, $quant);
 //   ->__set($property_name, $value);

    //$this->first()->__set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, array &$form_state) {
    die('WorthFieldItemList::defaultValuesForm');
    if (empty($this->getFieldDefinition()->default_value_function)) {
      $default_value = $this->getFieldDefinition()->default_value;
      $element = array();
      return $element;
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, array &$form_state) { }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, array &$form_state) {
    //return the interesting values from the form
    debug($form_state['values']);
    return array();
  }

  /**
   * {@inheritdoc}
   */
  //TODO remove this because there is no default value, I think
  //lets wait until I've played with configuring the fields
  public function _______getDefaultValue() {
    //seems to be called on submission of the settings form
    //this is what the parent function does
    return $this->getSetting('default_value');
    //but what we need is an array of currency => $raw value pairs

    $default_value = parent::getDefaultValue();
    //debug($default_value, 'default value from parent in WorthFieldItemList');
    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = true) {
    // Clear the values of properties for which no value or a value of 0 has been passed
    $this->list = array_intersect_key($this->list, array_filter($values));
    //We are constrained in that the db expects delta to be a number
    foreach ($values as $curr_id => $value) {
      $item = compact('curr_id', 'value');
      if (!isset($this->list[$curr_id])) {
        $this->list[$curr_id] = $this->createItem($curr_id, $item);
      }
      else {
        $this->list[$curr_id]->setValue($item, FALSE);
      }
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $values = array();
    foreach ($this->list as $item) {
      $val = $item->getValue($include_computed);
      $values[$val['curr_id']] = $val['value'];
    }
    return $values;
  }

  /**
   * get a (rich text) string representing the formatted values of all the currencies of the field
   * @param string $separator
   * @return string
   */
  public function getValueFormatted($separator = ", ") {
    foreach ($this->getValue() as $curr_id => $value) {
      $values[] = mcapi_currency_load($curr_id)->format($value);
    }
    return implode($separator, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function filterEmptyItems() {
    //do nothing because the items are filtered as they are set
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !array_filter($this->list);
  }

  public function __toString() {
    return $this->getValueFormatted();
  }
}
