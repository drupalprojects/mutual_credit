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
      $values = [$values];
    }

    //if the first value is not the wallet's owner, then shunt it in.
    $walletOwnerId = $this->getEntity()->getOwnerId();
    if (empty($values[0]['target_id']) or $values[0]['target_id'] != $walletOwnerId) {
      array_unshift($values, ['target_id' => $walletOwnerId]);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   *
   * The default value is the wallet owner
   *
   * @note On new wallets this didn't work because we can't getEntity() while it
   * is still being created.
   */
  public function applyDefaultValue($notify = TRUE) {
    if ($this->parent) {
      drupal_set_message("applying default value to wallet", 'warning');
      $wallet = $this->getEntity();
      if ($entity_type = $wallet->holder_entity_type->value) {
        $target_entity_id = \Drupal::entityTypeManager()
          ->getStorage($entity_type)
          ->load($wallet->holder_entity_id->value)->getOwnerId();
        $value = ['target_id' => $target_entity_id];
        $this->setValue($value, $notify);
      }
    }
    else {
      drupal_set_message("unable to apply default value to wallet burser", 'warning');
    }
    return $this;
  }

}
