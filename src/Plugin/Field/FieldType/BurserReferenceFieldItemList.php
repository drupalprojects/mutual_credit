<?php

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Represents a entity worth field.
 */
class BurserReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Support passing in only the value of the first item, either as a literal
    // (value of the first property) or as an array of properties.
    if (isset($values) && (!is_array($values) || (!empty($values) && !is_numeric(current(array_keys($values)))))) {
      $values = array(0 => $values);
    }
    $walletOwnerId = $this->getEntity()->getOwnerId();
    if ($values[0]['target_id'] != $walletOwnerId) {
      array_unshift($values, ['target_id' => $walletOwnerId]);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($property_name, $value) {
    // For empty fields, $entity->field->property = $value automatically
    // creates the item before assigning the value.
    $item = $this->first() ?: $this->appendItem();
    $item->__set('target_id', $this->getEntity()->getOwnerId());
  }


  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    $value = ['target_id' => $this->getEntity()->getOwnerId()];
    $this->setValue([$value], $notify);
    return $this;
  }

}
