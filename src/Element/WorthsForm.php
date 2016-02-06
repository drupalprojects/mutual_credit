<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\WorthsForm.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\mcapi\Exchange;

/**
 * Provides a worth field form element.
 *
 * @FormElement("worths_form")
 */
class Worthsform extends FormElement {
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
        [$class, 'process']
      ],
      '#element_validate' => [
        [$class, 'validate']
      ],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
      //'#theme_wrappers' => ['form_element'],
      '#attributes' => ['class' => ['worth-element']],
      '#config' => FALSE,
      '#allowed_curr_ids' => []
    ];
  }

  /**
   * helper function to ensure the worth field is showing the right subset of currencies
   * the #default_value is then used to build the widget
   */
  public static function processDefaults($element, FormStateInterface $form_state, $form) {
//    print_r($element['#default_value']);mtrace();
    if (empty($element['#default_value'])) {
      if (empty($element['#allowed_curr_ids'])) {
        $element['#allowed_curr_ids'] = array_keys(Exchange::currenciesAvailableToUser());
      }
      if (empty($element['#default_value'])) {
        foreach ($element['#allowed_curr_ids'] as $curr_id) {
          $element['#default_value'][] = [
            'curr_id' => $curr_id,
            'value' => ''
          ];
        }
      }
    }
    else {
      $map = Self::currMap($element['#default_value']);
      $element['#allowed_curr_ids'] = array_keys($map);
    }

    if ($element['#config'])  {
      foreach ($element['#default_value'] as $delta => &$worth) {
        if ($worth['value'] === ''){
          $worth['value'] = '0';
        }
      }
    }
    return $element;
  }

  /**
   * form element processor callback for 'worth'
   * takes the raw #default_values and makes child widgets according to the currency format.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    //we might need to filter #default_value not in config mode
    //by now the #default_value MUST contain some $items.
    foreach ($element['#default_value'] as $delta => $item) {
      $element[] = [
        '#type' => 'worth_form',
        '#default_value' => $item['value'],
        '#allowed_curr_ids' => [$item['curr_id']],
        '#config' => $element['#config'],
        '#oneofmany' => count($element['#default_value']) > 1
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   * How do we stop the values of the child elements overwriting the $output here?
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $output = [];
    if ($input === FALSE) {
      return $element['#default_value'];
    }
    foreach ($input as $key => $val) {
      $output[] = WorthForm::valueCallback($element[$key], $input[$key], $form_state);
    }
    //@todo it is this which is checked using WorthFieldList::filterEmptyItems
    return $output;
  }

  /**
   * validate callback
   * all validation takes place in the children
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

  private static function currMap($value) {
    if (empty($value)) {
      return array_keys(Exchange::currenciesAvailableToUser());
    }
    $map = [];
    foreach ($value as $key => $item) {
      $map[$item['curr_id']] = $key;
    }
    return $map;
  }


  /**
   * calculate a transaction quantity based on a provided formala and input quantity
  *
  * @param string $formula
  *   formula using [q] for base_quant. If it is just a number the number is returned as is
  *   otherwise [q]% should work or other variables to be determined
  *
  * @param integer $base_value
  *
  * @return interger | NULL
  */
  public static function calc($formula, $base_value) {
    if (is_null($base_value)) return 0;
    if (is_numeric($formula)) return $formula;
    $proportion = str_replace('%', '', $formula);
    if (empty($base_value)) $base_quant = 0;
    if (is_numeric($proportion)) {
      return $base_value * $proportion/100;
    }
    //$formula = str_replace('/', '//', $formula);
    $equation = str_replace('[q]', $base_value, $formula) .';';
    $val = eval('return '. $equation);
    if (is_numeric($val)) return $val;
    drupal_set_message(t('Problem with calculation for dependent transaction: @val', array('@val' => $val)));
  }
}
