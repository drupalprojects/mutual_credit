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
    return array(
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => array(
        array(get_class($this), 'process_minmax'),
      ),
      '#minus' => FALSE
    );
  }


  /**
   * processor for minmax element
   */
  public static function process_minmax($element) {
    $currency = Currency::load($element['#curr_id']);
    $element['limits'] = array(
      '#title' => t('!currencyname allowed balances', array('!currencyname' => $currency->label())),
      '#description' => array_key_exists('#description', $element) ? $element['#description'] : '',
      '#description_display' => 'before',
      '#type' => 'fieldset',
      //'#tree' => TRUE,
      'min' => array(
        '#title' => t('Minimum'),
        '#description' => t('Must be less than or equal to zero'),
        '#type' => 'worth',
        '#default_value' => array(
          array(
            'curr_id' => $element['#curr_id'],
            'value' => $element['#default_value']['min']
          )
        ),
        '#placeholder' => array(@$element['#placeholder']['min']
        ),
        '#minus' => TRUE
      ),
      'max' => array(
        '#title' => t('Maximum'),
        '#description' => t('Must be greater than 1.'),
        '#type' => 'worth',
        //we key the default value with the curr_id to make the saved settings easier to read
        '#default_value' => array(
          array(
            'curr_id' => $element['#curr_id'],
            'value' => $element['#default_value']['max']
          )
        ),
        '#placeholder' => array(@$element['#placeholder']['max']),
        '#weight' => 1,
        '#min' => 1
      )
    );
    return $element;
  }

  /**
   * value callback
   *
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (is_null($input))return;
//    debug($input['limits']);
    return array(
      'min' => Worth::valueCallback($element['limits']['min'], $input['limits']['min'], $form_state),
      'max' => Worth::valueCallback($element['limits']['max'], $input['limits']['max'], $form_state),
    );
  }

}


