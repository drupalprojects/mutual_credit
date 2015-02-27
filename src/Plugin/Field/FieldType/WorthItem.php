<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\WorthItem.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\FieldInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\mcapi\Entity\Currency;

/**
 * Plugin implementation of the 'worth' field type.
 *
 * @FieldType(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   description = @Translation("One or more values, each denominated in a currency"),
 *   default_widget = "worth",
 *   default_formatter = "worth",
 *   list_class = "\Drupal\mcapi\Plugin\Field\FieldType\WorthFieldItemList"
 * )
 */
class WorthItem extends FieldItemBase {

  public static function propertydefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['curr_id'] = DataDefinition::create('string')
      ->setLabel('Currency Id');
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel('Value');
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'curr_id' => array(
          'description' => 'The currency ID',
          'type' => 'varchar',//when to use varchar and when string?
          'length' => '8',
        ),
        'value' => array(
          'description' => 'Value',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
          'default' => 0
        )
      ),
    );
  }
  
  

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = true) {
    $this->set('curr_id', $value['curr_id']);
    $this->set('value', $value['value']);

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $val = $this->get('value');
    return empty($val);
  }

  /**
   * {@inheritdoc}
   */
  public function view($display_mode = []) {
    extract($this->getValue(FALSE));
    $currency = Currency::load($curr_id);
    if ($value) {
      $markup = $currency->format($value);
    }
    //optional special display if the item is zero in this currency
    elseif ($currency->zero) {
      $markup = \Drupal::config('mcapi.misc')->get('zero_snippet');
    }
    return array('#markup' => $markup);
  }
  
  
}
