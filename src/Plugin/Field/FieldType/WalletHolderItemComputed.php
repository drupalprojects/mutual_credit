<?php

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Defines the 'wallet_holder' computed entity field type.
 *
 *
 * @FieldType(
 *   id = "wallet_holder",
 *   label = @Translation("Wallet holder"),
 *   description = @Translation("Context aware wallets you can pay in/out of"),
 *   category = @Translation("Reference"),
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 *
 */
class WalletHolderItemComputed extends EntityReferenceItem {

  /**
   * The holder of the wallet
   * @var ContentEntity
   */
  private $holder;

  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    if ($property_name == 'entity') {
      if (!$this->holder) {
        $value = $this->getValue();
        $this->holder = \Drupal::entityTypeManager()
          ->getStorage($value['entity_type_id'])
          ->load($value['target_id']);
      }
      return $this->holder;
    }
    parent::__get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $wallet = $this->getEntity();
    // This value is as similar as we can get to a normal entity reference value
    // without having configured the target_type
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
  public function ___getTarget() {
    if (!isset($this->target) && isset($this->id)) {
      // If we have a valid reference, return the entity's TypedData adapter.
      $entity = \Drupal::entityTypeManager()
        ->getStorage($this->getTargetDefinition()->getEntityTypeId())
        ->load($this->id);
      $this->target = isset($entity) ? $entity->getTypedData() : NULL;
    }
    return $this->target;
  }

}
