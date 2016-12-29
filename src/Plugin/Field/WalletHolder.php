<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * A computed field adding upt the transaction volume of a wallet.
 */
class WalletVolume extends EntityReferenceFieldItemList {

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    mtrace();// looking for a chance to inject the $container
    return new static($definition, $name, $parent);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $wallet = $this->getEntity();
    return \Drupal::entityTypeManager()
      ->getStorage($wallet->holder_entity_type->value)
      ->load($wallet->holder_entity_id->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $wallet = $this->getEntity();
    $wallet->holder_entity_type->value = $value->getEntitytypeId();
    $wallet->holder_entity_id->value = $value->id();
  }

}

