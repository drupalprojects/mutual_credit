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
    if (isset($values) && (!is_array($values) || (!empty($values) && !is_numeric(current(array_keys($values)))))) {
      $values = array(0 => $values);
    }
    //if the first value is not the wallet's owner, then shunt it in.
    $walletOwnerId = $this->getEntity()->getOwnerId();
    if (isset($values[0]['target_id']) && $values[0]['target_id'] != $walletOwnerId) {
      array_unshift($values, ['target_id' => $walletOwnerId]);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   * This doesn't work because we can't getEntity() while it is still being created
   * @see presave()
   */
  public function _____applyDefaultValue($notify = TRUE) {
    if ($wallet->holder_entity_type->value == 'user') {
      $target = $wallet->holder_entity_id->value;
    }
    else {
      $target = \Drupal::entityTypeManager()
        ->getStorage($wallet->holder_entity_type->value)
        ->load($wallet->holder_entity_id->value)->getOwnerId();
    }
    $value = ['target_id' => $target];
    $this->setValue($value, $notify);
    return $this;
  }

  public function preSave() {
    $this->setValue([$this->getValue()]);
  }

}
