<?php

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * A computed field for the wallet holder
 *
 * @todo Try WalletHolderItemComputed again
 * @ddeprecated
 */
class WalletHolderItemComputedList extends EntityReferenceFieldItemList {

  /**
   * Stored holder entity (there's no list)
   *
   * @var ContentEntityInterface
   */
  protected $holder = NULL;

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
  public function __get($property_name) {
    if ($property_name == 'entity') {
      if (!$this->holder) {
        $value = $this->getValue();
        $this->holder = \Drupal::entityTypeManager()->getStorage($value['entity_type_id'])->load($value['target_id']);
      }
      return $this->holder;
    }
    return parent::__get($property_name);
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
  public function __set($property_name, $value) {
    echo $property_name; die('Yet to __set');
    // For empty fields, $entity->field->property = $value automatically
    // creates the item before assigning the value.
    $item = $this->first() ?: $this->appendItem();
    $item->__set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    return [$this->entity];
  }

}
