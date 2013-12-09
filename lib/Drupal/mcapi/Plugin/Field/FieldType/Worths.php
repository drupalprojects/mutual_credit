<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\Worth.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\ConfigFieldItemBase;
use Drupal\field\FieldInterface;
use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin implementation of the 'text' field type.
 *
 * @FieldType(
 *   id = "worths",
 *   label = @Translation("Worths"),
 *   description = @Translation("One or more values, each denominated in a currency"),
 *   default_widget = "worths",
 *   default_formatter = "worths"
 * )
 */
class Worths extends ConfigFieldItemBase {

  /**
   * Overrides \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    $definitions = array();
    foreach ($this->values as $currcode => $value) {
      if ($currency = entity_load('mcapi_currency', $currcode)) {
        $definitions[$currcode] = array(
          'type' => 'field_item:worth',
          'label' => $currency->name,
        );
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'currcode' => array(
          'description' => 'The currency ID',
          'type' => 'varchar',
          'length' => 32,
        ),
        'quantity' => array(
          'description' => 'Price',
          'type' => 'numeric',
          'size' => 'normal',
          'precision' => 8,
          'scale' => 2,
          'not null' => TRUE,
          'default' => 0
        )
      ),
    );
  }

}