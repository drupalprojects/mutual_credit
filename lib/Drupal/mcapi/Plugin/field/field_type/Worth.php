<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\field\Worth.
 */

namespace Drupal\mcapi\Plugin\field\field_type;

use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;
use Drupal\field\FieldInterface;

/**
 * Plugin implementation of the 'text' field type.
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

      static::$propertyDefinitions['currcode'] = array(
        'type' => 'string',
        'label' => t('Currency ID'),
      );
      static::$propertyDefinitions['quantity'] = array(
        'type' => 'float',
        'label' => t('Price'),
      );
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

}
