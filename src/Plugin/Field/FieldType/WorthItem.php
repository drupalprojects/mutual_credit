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
    return [
      'columns' => [
        'curr_id' => [
          'description' => 'The currency ID',
          'type' => 'varchar',//when to use varchar and when string?
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
   * format the worth value according to 3 view modes, normal, decimal and raw
   * taking into account that there may be special formatting if worth is 0
   */
  public function view($mode = 'normal') {
    extract($this->getValue(FALSE));
    $currency = Currency::load($curr_id);
    if ($mode == 'raw') {
      $markup = $value;
    }
    else {
      if ($value) {
        $markup = $currency->format($value);
        if ($mode == 'decimal') {
          //this could be smarter, but for now we're just going to strip out
          //all the non-numeric characters
          //can't think how best to do it with regex
          $new = '';
          foreach(str_split($markup) as $char) {
            if (is_numeric($char)) $new .= $char;
          }
          $markup = $new;
        }
      }
      //optional special display if the item is zero in this currency
      elseif ($currency->zero) {
        if ($mode == 'decimal') {
          $markup = 0;
        }
        $markup = \Drupal::config('mcapi.misc')->get('zero_snippet');
      }
    }
    return array('#markup' => $markup);
  }

}
