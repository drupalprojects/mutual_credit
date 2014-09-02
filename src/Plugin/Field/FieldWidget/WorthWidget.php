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
use Drupal\Component\Utility\String;
use Drupal\mcapi\Entity\Exchange;

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
    //element may already contain #allowed_curr_ids
    return $element + array(
      '#title' => String::checkPlain($this->fieldDefinition->getLabel()),
      '#title_display' => 'attribute',
      '#type' => 'worth',
      '#default_value' => $items->getValue(),
      '#allowed_curr_ids' => array_keys(exchange_currencies(Exchange::referenced_exchanges(NULL, TRUE))),
      //'#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * {@inheritdoc}
   */

  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    echo "\Drupal\mcapi\Plugin\Field\FieldWidget\WorthWidget::errorElement hasn't been written yet";

    if ($violation->arrayPropertyPath == array('format') && isset($element['format']['#access']) && !$element['format']['#access']) {
      // Ignore validation errors for formats if formats may not be changed,
      // i.e. when existing formats become invalid. See filter_process_format().
      return FALSE;
    }
    return $element;
  }

}
