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
 *   id = "currency_time_single",
 *   label = @Translation("Single Field"),
 *   field_types = {
 *     "currency_type_time"
 *   }
 * )
 */
class CurrencyTimeSingleWidget extends CurrencySingleWidgetBase {

}