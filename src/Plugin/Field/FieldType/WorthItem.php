<?php

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;

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

  /**
   * {@inheritdoc}
   */
  public static function propertydefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['curr_id'] = DataDefinition::create('string')
      ->setLabel(t('@label ID', ['Currency']));
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Value'));
    $properties['currency'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('Currency'))
      ->setComputed(TRUE)
      ->addConstraint('EntityType', 'mcapi_currency')
      ->setClass('\Drupal\mcapi\Plugin\Field\CurrencyComputedProperty')
      ->setSetting('currency source', 'curr_id');
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
          'default' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->curr_id and $this->currency->zero) {
      return FALSE;
    }
    return $this->value == 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    return [
      'value' => $field_definition->currency->sampleValue(),
      'curr_id' => $field_definition->currency->id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    if ($property_name == 'currency') {
      $this->curr_id = $this->currency->id;
    }
    parent::onChange($property_name, $notify);
  }

  public function __toString() {
    return $this->format(Currency::DISPLAY_NORMAL);
  }

  public function format($mode) {
    return (string)$this->currency->format($this->value, $mode, FALSE);
  }

  /**
   * Set the value of the field from the formatted value coming, say from a
   * widget.
   *
   * @param mixed $formatted_value
   *   Value or array from currency widget
   * @param string $curr_id
   *
   * @deprecated not used.
   */
//  public function setValueFormatted($formatted_value, $curr_id = NULL) {
//    if (empty($curr_id)) {
//      $currency = reset(Currency::loadMultiple());
//    }
//    foreach ($this->values as $key => $value) {
//      if ($value['curr_id'] == $curr_id) {
//        $reuse = 1;
//        continue;
//      }
//    }
//    $value = [
//      'curr_id' => $this->currency->id(),
//      'value' => $this->currency->unformat((array)$formatted_value)
//    ];
//    if (!$reuse) {
//      $key++;
//    }
//    $this->values[$key] = $value;
//  }


}
