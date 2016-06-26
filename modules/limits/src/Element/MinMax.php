<?php

namespace Drupal\mcapi_limits\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\mcapi\Entity\Currency;

/**
 * Provides a form element for selecting a transaction state.
 *
 * @FormElement("minmax")
 */
class MinMax extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [
        [get_class($this), 'processMinmax'],
      ],
    ];
  }

  /**
   * Processor for minmax element.
   */
  public static function processMinmax($element) {
    $currency = Currency::load($element['#curr_id']);
    $element += [
      '#title' => t('%currencyname allowed balances', ['%currencyname' => $currency->label()]),
      '#description' => array_key_exists('#description', $element) ? $element['#description'] : '',
      '#description_display' => 'before',
      '#type' => 'container',
      'min' => [
        '#title' => t('Minimum'),
        '#description' => t('Must be less than or equal to zero'),
        '#type' => 'worth_form',
        '#config' => TRUE,
        '#default_value' => [
          'curr_id' => $element['#curr_id'],
          'value' => $element['#default_value']['min'],
        ],
        '#allowed_curr_ids' => [$element['#curr_id']],
        '#placeholder' => @$element['#placeholder']['min'],
        '#minus' => TRUE,
      ],
      'max' => [
        '#title' => t('Maximum'),
        '#description' => t('Must be greater than 1.'),
        '#type' => 'worth_form',
        '#config' => TRUE,
        '#default_value' => [
          'curr_id' => $element['#curr_id'],
          'value' => $element['#default_value']['max'],
        ],
        '#allowed_curr_ids' => [$element['#curr_id']],
        '#placeholder' => @$element['#placeholder']['max'],
        '#weight' => 1,
        '#min' => 1,
      ],
    ];
    return $element;
  }

}
