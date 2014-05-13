<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\DataType\Worth.
 */

namespace Drupal\mcapi\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\mcapi\Plugin\CurrencyTypePluginManager;

/**
 * The "worth" data type.
 *
 * @DataType(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   definition_class = "\Drupal\mcapi\Plugin\DataType\WorthDataDefinition",
 *   list_class = "\Drupal\Core\TypedData\Plugin\DataType\ItemList",
 *   list_definition_class = "\Drupal\Core\TypedData\ListDataDefinition"
 * )
 */

class Worth extends Map{

  function __construct() {
    die('we are using \Drupal\mcapi\Plugin\DataType\Worth!');
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
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
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }


  /**
   * {@inheritdoc}
   * //TODO is this needed?
   */
  public function getConstraints() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state, $has_data) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function instanceSettingsForm(array $form, array &$form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  //shouldn't we be using the worthformatter here?
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
