<?php

/**
 * @file
 * Contains \Drupal\mcapi_limits\Element\MinMax.
 * A wrapper around 2 worth values
 */

namespace Drupal\mcapi_limits\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\mcapi\Element\Worth;
use Drupal\mcapi\Entity\Currency;

/**
 * Provides a form element for selecting a transaction state
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
        [get_class($this), 'process_minmax'],
      ]
    ];
  }


  /**
   * processor for minmax element
   */
  public static function process_minmax($element) {
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
          'value' => $element['#default_value']['min']
        ],
        '#allowed_curr_ids' => [$element['#curr_id']],
        '#placeholder' => @$element['#placeholder']['min'],
        '#minus' => TRUE
      ],
      'max' => [
        '#title' => t('Maximum'),
        '#description' => t('Must be greater than 1.'),
        '#type' => 'worth_form',
        '#config' => TRUE,
        //we key the default value with the curr_id to make the saved settings easier to read
        '#default_value' => [
          'curr_id' => $element['#curr_id'],
          'value' => $element['#default_value']['max']
        ],
        '#allowed_curr_ids' => [$element['#curr_id']],
        '#placeholder' => @$element['#placeholder']['max'],
        '#weight' => 1,
        '#min' => 1
      ]
    ];
    return $element;
  }

}
