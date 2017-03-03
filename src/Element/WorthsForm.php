<?php

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a worth field form element.
 *
 * @FormElement("worths_form")
 */
class WorthsForm extends FormElement {
  use \Drupal\Core\Render\Element\CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#tree' => TRUE,
      '#process' => [
        [$class, 'processDefaults'],
        [$class, 'process'],
      ],
      '#element_validate' => [
        [$class, 'validate'],
      ],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
      // '#theme_wrappers' => ['form_element'],.
      '#attributes' => ['class' => ['worth-element']],
      '#config' => FALSE,
      '#allowed_curr_ids' => [],
    ];
  }

  /**
   * Ensure the worth field is showing the right subset of currencies.
   *
   * @note The #default_value is then used to build the widget.
   */
  public static function processDefaults($element, FormStateInterface $form_state, $form) {
    // Populate #allowed_curr_ids from the default value OR all currencies.

    if (empty($element['#default_value']) or empty($element['#default_value'][0])) {
      if (empty($element['#allowed_curr_ids'])) {
        $element['#allowed_curr_ids'] = array_keys(mcapi_currencies_available());
      }
      $element['#default_value'] = [];

      foreach ($element['#allowed_curr_ids'] as $curr_id) {
        $element['#default_value'][] = [
          'curr_id' => $curr_id,
          'value' => '',
        ];
      }
    }
    else {
      $map = Self::currMap($element['#default_value']);
      $element['#allowed_curr_ids'] = array_keys($map);
    }

    if ($element['#config']) {
      foreach ($element['#default_value'] as &$worth) {
        if ($worth['value'] === '') {
          $worth['value'] = '0';
        }
      }
    }
    return $element;
  }

  /**
   * Form element processor callback.
   *
   * Make widget(s) according to each currency format of each #default_value.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    // We might need to filter #default_value not in config mode
    // by now the #default_value MUST contain some $items.
    foreach ($element['#default_value'] as $delta => $item) {
      $element[$delta] = [
        '#type' => 'worth_form',
        '#default_value' => $item['value'],
        '#allowed_curr_ids' => [$item['curr_id']],
        '#config' => $element['#config'],
        '#oneofmany' => count($element['#default_value']) > 1,
        '#theme_wrappers' => [],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Stop the values of the child elements overwriting the $output here?
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $output = [];
    if ($input === FALSE) {
      return $element['#default_value'];
    }
    if ($input === NULL) {
      return [];
    }
    foreach ($input as $key => $val) {
      $output[] = WorthForm::valueCallback($element[$key], $input[$key], $form_state);
    }
    // @todo it is this which is checked using WorthFieldList::filterEmptyItems
    return $output;
  }

  /**
   * Validate callback.
   *
   * All validation takes place in the children.
   */
  public static function validate(&$element, FormStateInterface $form_state) {
    foreach ($element['#value'] as $key => $val) {
      if (!isset($val['value'])) {
        unset($element['#value'][$key]);
      }
    }
    $element['#value'] = array_values($element['#value']);
    $form_state->setValue($element['#name'], $element['#value']);
  }

  /**
   * Helper.
   *
   * Map the currencies to worth deltas.
   *
   * @param array $value
   *   A value from a worth field, containing curr_id and value.
   *
   * @return array
   *   The deltas, keyed by curr_id.
   */
  private static function currMap($value) {
    if (empty($value)) {
      return array_keys(mcapi_currencies_available());
    }
    $map = [];
    foreach ($value as $key => $item) {
      $map[$item['curr_id']] = $key;
    }
    return $map;
  }

}
