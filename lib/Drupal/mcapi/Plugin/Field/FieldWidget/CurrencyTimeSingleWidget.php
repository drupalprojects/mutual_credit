<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\Field\FieldWidget\CurrencyTimeSingleWidget.
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textfield' widget.
 *
 * @FieldWidget(
 *   id = "currency_time_single",
 *   label = @Translation("Single Field"),
 *   field_types = {
 *     "currency_type_time"
 *   },
 *   settings = {
 *     "placeholder" = "h:m:s"
 *   }
 * )
 */
class CurrencyTimeSingleWidget extends CurrencySingleWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element+= array(
      '#type' => 'textfield',
      '#size' => 6,
    );

    return $element;
  }

  public function renderValue($value) {
    if (!empty($value)) {
      $hours = ($value - ($value % 3600)) / 3600;
      $minutes = ($value - ($hours * 3600) - ($value % 60)) / 60;
      $seconds = $value % 60;

      return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ($seconds ? ':' . $seconds : '');
    }
  }

  public function asInteger($value) {
    if ($value || $value === 0) {
      $parts = array_reverse(explode(':', $value));
      $hours = array_pop($parts);
      $minutes = array_pop($parts);
      $seconds = array_pop($parts);

      return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
  }
}
