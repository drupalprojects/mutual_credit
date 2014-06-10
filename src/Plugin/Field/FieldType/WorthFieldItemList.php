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
   * We have to override this because the default behaviour is to create
   * the list with an empty item keyed 0, then to set the value of that default item
   * Because we are using the currency id as the list key, we can't create a worth item without it
   */
  public function set($property_name, $value) {//TODO make this work
    $this->worthSet($value);
  }

  /**
   * {@inheritdoc}
   */
  //$values needs to be array(array('curr_id' => 1, 'value' => 100))
  public function setValue($values, $notify = true) {
    // Clear the values of properties for which no value or a value of 0 has been passed
    $this->list = array_intersect_key($this->list, array_filter($values));
    //We are constrained in that the db expects delta to be a number
    foreach ($values as $value) {
      $this->worthSet($value);
    }
  }

  /**
   * set the value, ensuring that no two curr_ids are the same
   * @param unknown $val
   */
  private function worthSet($val) {
    $key = 0;
    foreach ($this->list as $key => $item) {
      if ($item->get('curr_id') == $val['curr_id']) {
        $this->list[$key]->setValue($val, FALSE);
        return;
      }
      $key++;
    }
    $this->list[$key] = $this->createItem($key, $val);//or $item???

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
    return $this->view();
  }

  //this is how the default widget, StringFormatter get the text out
  public function value() {
    return $this->view();
  }

  /**
   * {@inheritdoc}
   */
  public function view($display_options = array()) {
    foreach ($this->list as $item) {
      $values[] = $item->view($display_options);
    }
    $separator = count($values) > 1 ? \Drupal::config('mcapi.misc')->get('worths_delimiter') : '';
    return implode($separator, $values);
  }

}
