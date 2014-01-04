<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\Worths.
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

  public $delimiter;

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::get().
   */
  public function get($property_name) {
    if (entity_load('mcapi_currency', $property_name)) {
      if (!isset($this->properties[$property_name])) {
        $value = array(
          'currcode' => $property_name,
        );
        if (isset($this->values[$property_name])) {
          $value = $this->values[$property_name];
        }
        // If the property is unknown, this will throw an exception.
        $this->properties[$property_name] = \Drupal::typedData()->getPropertyInstance($this, $property_name, $value);
      }
    }
    return $this->properties[$property_name];
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    return $this->get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
    }
    $this->values = $values;
    $this->properties = array();

    // Add any new currencies
    foreach ($this->values as $currcode => $value) {
      if (!isset($this->properties[$currcode]) && ($currency = entity_load('mcapi_currency', $currcode))) {
        $this->get($currcode)->setValue($value, FALSE);
        unset($this->values[$currcode]);
      }
    }

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Overrides \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    $definitions = array();
    foreach ($this->properties as $currcode => $property) {
      if ($property->value !== NULL && $currency = entity_load('mcapi_currency', $currcode)) {
        $definitions[$currcode] = array(
          'type' => 'field_item:worth',
          'label' => $currency->name,
        );
      }
    }

    return $definitions;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    if ($currency = entity_load('mcapi_currency', $name)) {
      return array(
        'type' => 'field_item:worth',
        'label' => $currency->name,
      );
    }
    else {
      return FALSE;
    }
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
        'value' => array(
          'description' => 'Value',
          'type' => 'integer',
          'size' => 'normal',
          'not null' => TRUE,
          'default' => 0
        )
      ),
    );
  }

  public function __toString() {
    return $this->getString();
  }

  public function getString($delimiter = '') {
    foreach ($this->properties as $worth) {
      $output[] = $worth->getString();
    }
    if (count($output) > 1) {
      if (empty($delimiter)) {
        $delimiter = \Drupal::config('mcapi.misc')->get('worths_delimiter');
      }
      return implode($delimiter, $output);
    }
    return $output[0];
  }

}

