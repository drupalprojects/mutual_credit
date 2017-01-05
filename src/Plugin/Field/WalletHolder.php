<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * A computed field adding up the transaction volume of a wallet.
 */
class WalletHolder extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $wallet = $this->getEntity();
    return [
      'entity_type_id' => $wallet->holder_entity_type->value,
      'target_id' => $wallet->holder_entity_id->value,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $wallet = $this->getEntity();
    $wallet->holder_entity_type->value = $value->getEntitytypeId();
    $wallet->holder_entity_id->value = $value->id();
  }


  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    if ($property_name == 'entity') {
      return \Drupal::entityTypeManager()
      ->getStorage($this->getEntity()->holder_entity_type->value)
      ->load($this->getEntity()->holder_entity_id->value);
    }
    // For empty fields, $entity->field->property is NULL.
    if ($item = $this->first()) {
      return $item->__get($property_name);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    return [$this->entity];
  }

}

