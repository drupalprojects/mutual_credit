<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Currency.
 */

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Mcapi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * Provides a widget to select currencies
 *
 * @FormElement("mcapi_currency_select")
 */
class Currency extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#input' => TRUE,
      '#process' => [
        [get_class($this), 'process_currcodes'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#multiple' => FALSE,
      '#status' => TRUE //filter only for active currences
    );
  }

  /**
   * process callback
   */
  static function process_currcodes($element) {
    $conditions = [];
    if ($element['#status']) {
      $conditions['status'] = TRUE;
    }
    if (empty($element['#options']) && !empty($element['#curr_ids'])) {
      //shows the intersection of all currencies and currencies provided
      $element['#options'] = array_intersect_key(
        Mcapi::entityLabelList('mcapi_currency', $element['#curr_ids']),
        entity_load_multiple_by_properties('mcapi_currency', $conditions)
      );
    }
    elseif (empty($element['#options'])) {
      $element['#options'] = Mcapi::entityLabelList('mcapi_currency', $conditions);
    }
    elseif ($element['#options'] == 'all') {
      $element['#options'] = Mcapi::entityLabelList('mcapi_currency');
    }

    if (count($element['#options']) == 1) {
      $element['#type'] = 'hidden';
      $element['#value'] = key($element['#options']);
      unset($element['#theme_wrappers']);
    }
    elseif ($element['#multiple']) {
      //have to do some of the checkbox processing manually coz we missed it
      $element['#type'] = 'checkboxes';
      $element['#value'] = array_filter($element['#default_value']);
      $element = Checkboxes::processCheckboxes($element);
    }
    else {
      $element['#theme'] = 'select';
    }
    return $element;
  }

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== NULL) {
      dsm('\Drupal\mcapi\Element\Currency::valueCallback'.$input);
      return $input;
    }
  }
}