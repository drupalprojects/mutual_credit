<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\WorthItem.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;

/**
 * Plugin implementation of the 'worth' field type.
 *
 * @FieldType(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   description = @Translation("One or more values, each denominated in a currency"),
 *   default_widget = "worth",
 *   default_formatter = "worth",
 *   list_class = "\Drupal\mcapi\Plugin\Field\FieldType\WorthFieldItemList",
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {
 *         "Range" = {
 *           "min" = "0",
 *           "max" = "2147483648",
 *         }
 *       }
 *     }
 *   }
 * )
 */
class WorthItem extends FieldItemBase {

  public static function propertydefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['curr_id'] = DataDefinition::create('string')
      ->setLabel(t('@label ID', ['Currency']));
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Value'));
    $properties['currency'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('Currency'))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create('mcapi_currency'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'curr_id' => [
          'description' => 'The currency ID',
          'type' => 'varchar',
          'length' => '8',
        ],
        'value' => [
          'description' => 'Value',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'default' => 0
        ]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = true) {
    if (!isset($value['value'])) {
      return;
    }
    $this->set('curr_id', $value['curr_id']);
    $this->set('value', $value['value']);
    //set the computed field
    $this->set('currency', Currency::load($value['curr_id']), $notify);
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->currency->zero) {
      return FALSE;
    }
    return $this->get('value')->getValue() == 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    return [
      'value' => $field_definition->currency->sampleValue(),
      'curr_id' => $field_definition->currency->id
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'value';
  }
}
