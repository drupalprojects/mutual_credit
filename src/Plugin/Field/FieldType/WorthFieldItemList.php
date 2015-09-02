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



}
