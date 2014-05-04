<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\Field\FieldWidget\CurrencySingleWidget.
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textfield' widget.
 *
 * @FieldWidget(
 *   id = "currency_decimal_single",
 *   label = @Translation("Single Field"),
 *   field_types = {
 *     "currency_type_decimal"
 *   }
 * )
 */
class CurrencyDecimalSingleWidget extends CurrencySingleWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      "placeholder" => ""
    );
  }
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element+= array(
      '#type' => 'number',
      '#min' => 0,
      '#step' => 'any',
      '#size' => 10,
      '#placeholder' => $this->getFieldSetting('placeholder'),
    );

    return $element;
  }

  public function renderValue($value) {
    return empty($value) ? $value : number_format($value / pow(10, $this->getFieldSetting('scale')), $this->getFieldSetting('scale'));
  }

  /**
   * {@inheritdoc}
   */
  public function asInteger($value) {
    return empty($value) ? NULL : $value * pow(10, $this->getFieldSetting('scale'));
  }
}