<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\WorthFieldItemList.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a configurable entity worth field.
 */
class WorthFieldItemList extends FieldItemList {

  /*
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);//which creates an empty first list item
    $this->definition = $definition;
    $this->parent = $parent;
    $this->name = $name;
  }
   *
   */

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

  public function __toString() {
    return render($this->view());
  }

  /**
   * {@inheritdoc}
   */
  public function view($display_options = []) {
    foreach ($this->list as $item) {
      $renderable = $item->view($display_options);
      $values[] = \Drupal::service('renderer')->render($renderable);
    }
    $separator = count($values) > 1 ? \Drupal::config('mcapi.misc')->get('worths_delimiter') : '';
    return array(
      0 => array('#markup' => implode($separator, $values))
    );
  }


  /**
   * undocumented
   * used in function intertrading_new_worths()
   * @return integer | NULL
   *   NULL if the currency isn't known
   * @todo tidy this up
   */
  public function val($curr_id) {
    foreach ($this->list as $item) {
      if ($item->curr_id == $curr_id) return $item->value;
    }
  }

  public function currencies($full = FALSE) {
    $c = [];
    foreach ($this->list as $item) {
      if ($full) {
        $c[$item->curr_id] = mcapi_currency_load($item->curr_id);
      }
      else {
        $c[] = $item->curr_id;
      }
    }
    return $c;
  }



}
