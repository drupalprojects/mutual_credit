<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\Field\FieldWidget\CurrencySingleWidget.
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\field\Plugin\Type\Widget\WidgetBase;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textfield' widget.
 *
 * @FieldWidget(
 *   id = "currency_select",
 *   label = @Translation("Select Field"),
 *   field_types = {
 *     "currency_type"
 *   }
 * )
 */
class CurrencySplitWidget extends WidgetBase {
}