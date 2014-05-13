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

/**
 * Plugin implementation of the 'worth' field type.
 *
 * @FieldType(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   description = @Translation("One or more values, each denominated in a currency"),
 *   default_widget = "worth_known_cur",
 *   default_formatter = "worth",
 *   list_class = "\Drupal\mcapi\Plugin\Field\FieldType\WorthFieldItemList"
 * )
 */
class WorthItem extends FieldItemBase {

  public static function propertydefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['currcode'] = DataDefinition::create('string')
      ->setLabel('Currency Id');
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel('Value');
    //calculated property - not sure if this is useful enough
    /*
     * delete this and the class \Drupal\mcapi\WorthCurrency
    $properties['currency'] = DataDefinition::create('entity:mcapi_currency')
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass('\Drupal\mcapi\WorthCurrency');
      */
    return $properties;
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
  public function getItemDefinition() {
    die('WorthItem getItemDefinition');
  }


  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = true) {
    $this->set('value', $value['value']);
    $this->set('currcode', $value['currcode']);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('value'));
  }

}
