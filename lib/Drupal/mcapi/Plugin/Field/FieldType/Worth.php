<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\Worth.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\ConfigFieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\FieldInterface;
use Drupal\mcapi\Plugin\CurrencyTypePluginManager;

/**
 * Plugin implementation of the 'worth' field type.
 *
 * @FieldType(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   description = @Translation("One or more values, each denominated in a currency"),
 *   default_widget = "worth",
 *   default_formatter = "worth"
 * )
 */
class Worth extends ConfigFieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions = parent::getPropertyDefinitions();

      static::$propertyDefinitions['currcode'] = DataDefinition::create('string')
        ->setLabel('Currency Id');
      static::$propertyDefinitions['value'] = DataDefinition::create('integer')
        ->setLabel('Value');
      static::$propertyDefinitions['currency'] = DataDefinition::create('entity:currency')
        ->setComputed(TRUE)
        ->setReadOnly(TRUE)
        ->setClass('\Drupal\mcapi\WorthCurrency');
    }
    return static::$propertyDefinitions;
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

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    $element = array();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    $element = array();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    if ($this->value === NULL) {
      return;
    }
    return $this->currency->format($this->value);
  }

  public function __toString() {
    return $this->getString();
  }
}
