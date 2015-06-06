<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldWidget\WorthWidget.
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Plugin implementation of the 'worth' widget.
 * No settings, I think
 *
 * @FieldWidget(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   multiple_values = 1,
 *   field_types = {
 *     "worth"
 *   }
 * )
 */
class WorthWidget extends WidgetBase {

  /**
   * Returns the form for a single field widget.
   *
   * @see \Drupal\Core\Field\WidgetInterface
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    //$element contains no meaningful information
    $element += array(
      '#title' => Safemarkup::checkPlain($this->fieldDefinition->label()),
      '#title_display' => 'attribute',
      '#type' => 'worth',
      '#default_value' => $items->getValue(),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   * @todo test this errorElement
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    //returns the whole element - all currencies will be shown in red
    return $element;
  }
}
