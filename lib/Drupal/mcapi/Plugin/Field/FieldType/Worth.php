<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldType\Worth.
 */

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\ConfigFieldItemBase;
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
  public function __get($name) {
    if ($name == 'currency') { //FIXME: This is a giant hack!
      return entity_load('mcapi_currency', $this->currcode);
    }
    return parent::__get($name);
  }

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
      static::$propertyDefinitions['value'] = array(
        'type' => 'integer',
        'label' => t('Value'),
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

  /**
   * {@inheritdoc}
   */
  public function getString() {
    $value = NULL;
    switch ($this->currency->type) {
      case 'time':
        $hours = ($this->value - ($this->value % 3600)) / 3600;
        $minutes = ($this->value - ($hours * 3600) - ($this->value % 60)) / 60;
        $seconds = $this->value % 60;

        $value = $hours . ':' . $minutes . ($seconds ? ':' . $seconds : '');
        break;

      case 'decimal':
        $value = empty($this->value) ? $this->value : $this->value / pow(10, $this->currency->settings['scale']);
        break;
    }

    return $this->currency->prefix . $value . $this->currency->suffix;
  }

  public function toString() {
    return $this->getString();
  }
}
