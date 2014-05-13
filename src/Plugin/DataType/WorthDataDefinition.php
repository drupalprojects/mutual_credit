<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\DataType\WorthDataDefinition.
 */

namespace Drupal\mcapi\Plugin\DataType;

use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * A typed data definition class for defining maps.
 */
class WorthDataDefinition extends MapDataDefinition {

  function getPropertyDefinitions() {

    $properties['currcode'] = DataDefinition::create('string')
    ->setLabel(t('Currency ID'));

    $properties['value'] = DataDefinition::create('integer')
    ->setLabel(t('Quantity'))
    ->setDescription(t('Raw units'));

    //TODO
    //if we don't use this then we can delete the class
    //    $properties['currency'] = DataDefinition::create('any')
    //    ->setComputed(TRUE)
    //    ->setReadOnly(TRUE)
    //    ->setClass('\Drupal\mcapi\WorthCurrency');

    return $properties;
  }

  /**
   * Creates a new map definition.
   *
   * @param string $type
   *   (optional) The data type of the map. Defaults to 'map'.
   *
   * @return static
   */
  public static function create($type = 'map') {
    $definition['type'] = $type;
    return new static($definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($data_type) {
    return static::create($data_type);
  }

  /**
   * Sets the definition of a map property.
   *
   * @param string $name
   *   The name of the property to define.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   (optional) The property definition to set, or NULL to unset it.
   *
   * @return $this
   */
  public function setPropertyDefinition($name, DataDefinitionInterface $definition = NULL) {
    if (isset($definition)) {
      $this->propertyDefinitions[$name] = $definition;
    }
    else {
      unset($this->propertyDefinitions[$name]);
    }
    return $this;
  }

}
