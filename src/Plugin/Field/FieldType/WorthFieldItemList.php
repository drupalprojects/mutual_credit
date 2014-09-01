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
    $this->list = array();
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
    die("WorthFieldItemList:first should never be called.");
  }


  /**
   * We have to override this because the default behaviour is to create
   * the list with an empty item keyed 0, then to set the value of that default item
   * Because we are using the currency id as the list key, we can't create a worth item without it
   */
  public function set($property_name, $value) {
    $key = 0;
    foreach ($this->list as $key => $item) {
      if ($item->curr_id == $value['curr_id']) {
        $this->list[$key]->setValue($value, FALSE);
        return;
      }
      $key++;
    }
    //if we didn't override anything then create a new item
    $this->list[$key] = $this->createItem($key, $value);//or $item???
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

  /**
   * {@inheritdoc}
   */
  public function view($display_options = array()) {
    foreach ($this->list as $item) {
      $renderable = $item->view($display_options);
      $values[] = drupal_render($renderable);
    }
    $separator = count($values) > 1 ? \Drupal::config('mcapi.misc')->get('worths_delimiter') : '';
    return array(
      0 => array('#markup' => implode($separator, $values))
    );
  }

  /**
   * undocumented
   * used in function intertrading_new_worths()
   * @todo tidy this up
   */
  public function curr_ids() {
    foreach ($this->list as $item) {
      $curr_ids[] = $item->curr_id;
    }
    return $curr_ids;
  }

  /**
   * undocumented
   * used in function intertrading_new_worths()
   * @todo tidy this up
   */
  public function val($curr_id) {
    foreach ($this->list as $item) {
      if ($item->curr_id == $curr_id) return $item->value;
    }
    return 0;
  }
}
