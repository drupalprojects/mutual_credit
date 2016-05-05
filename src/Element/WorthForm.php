<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\WorthForm.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Exchange;

/**
 * Provides a worth field form element.
 *
 * @FormElement("worth_form")
 */
class Worthform extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'process']
      ],
      '#element_validate' => [
        [$class, 'validate']
      ],
      '#attached' => ['library' => ['mcapi/mcapi.worth.element']],
      '#attributes' => ['class' => ['worth']],
      '#minus' => FALSE,
      '#config' => FALSE,
      '#min' => 0,
      '#max' => NULL,
      '#allowed_curr_ids' => [],
      '#oneofmany' => FALSE,
      '#theme_wrappers' => ['form_element']
    ];
  }

  /**
   * form element processor callback for 'worth'
   * takes the raw #default_values and makes child widgets according to the currency format.
   * receives something like this:
   * [
        '#type' => 'worth_form',
        '#default_value' => [
          'curr_id',
          'value'
         ],
        '#allowed_curr_ids' => [cc],
        '#config' => $element['#config'],
        '#max' => NULL,
        '#min' => 0,
      ];
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    if (empty($element['#allowed_curr_ids'])) {
      $element['#allowed_curr_ids'] = array_keys(Currency::loadMultiple());
    }
    //we might need to filter #default_value not in config mode
    if (count($element['#allowed_curr_ids']) == 1) {
      $currency = Currency::load(strtolower(reset($element['#allowed_curr_ids'])));//@todo remove strtolower

      $value = abs($element['#default_value']);//see #minus
      if ($element['#config'] && (!is_numeric($value) || $value == 0)) {
        $value_parts = [];
      }
      else {
        $value_parts = (strlen($value)) ?
          $currency->formattedParts(intval($value)) :
          [];
      }
      Self::subfields($element, $currency, $value_parts);
    }
    //multiple choice of currencies, but only showing one widget
    else {
      $currencies = Currency::loadMultiple($element['#allowed_curr_ids']);
      $element['curr_id'] = [
        '#type' => 'select',
        '#options' => \Drupal\mcapi\Mcapi::entityLabelList('mcapi_currency', $currencies),
        '#required' => TRUE
      ];
      $element['value'] = [
        '#type' => 'number',
        '#default_value' => $element['#default_value']
      ];
      if ($element['#config']) {
        $element['value']['#size'] = 10;
        $element['value']['#maxlength'] = 10;
      }
    }
    return $element;
  }

  static function subfields(&$element, $currency, $value_parts) {
    $element['curr_id'] = [
      '#type' => 'hidden',
      '#value'  => $currency->id()
    ];
    if ($element['#minus']) {
      $element[-1] = [
        '#markup' => '-',
        '#weight' => -1
      ];
    }
    foreach ($currency->format as $i => $component) {
      $element[$i] = ['#weight' => $i];
      if ($i % 2) { //an odd number so render a form element
        $step = 1;
        $options = [];
        if (strpos($component, '/')) {//we need to make a dropdown
          list($component, $divisor) = explode('/', $component);
          $base = $component + 1;
          $step = $base / $divisor;
          for ($j=0; $j < $divisor; $j++) {
            $v = $j* $step;
            $options[intval($v)] = $v;
          }
        }
        $element[$i] += [
          '#default_value' => @$value_parts[$i],
          '#theme_wrappers' => []
        ];
        //if a preset value isn't in the $options
        //then we ignore the options and use the numeric sub-widget
        if (isset($options) && array_key_exists(@$value_parts[$i], $options)) {
          $element[$i] += [
            '#type' => 'select',
            '#options' => $options,
          ];
        }
        else {
          $size = strlen($component);
          $element[$i] += [
            '#type' => 'number',
            '#min' => 0,
            '#step' => $step,
            '#size' => $size,//no effect in opera
            '#maxlength' => $size,
            '#attributes' => ['style' => 'width:'.($size +1) .'em;']
          ];
          if ($i == 1){
            //for the first part, 000 translates to a max of 999
            $element[$i]['#max'] = pow(10, strlen($component))-1;
          }
          else {
            //for subsequent parts, the $component is the max
            $element[$i]['#max'] = $component;//first component matters only for the num of digits
          }

          if ($element['#config']) {
            //placeholder's only work in config fields
            $element['#type'] = 'textfield';
            if (isset($element['#placeholder'])) {
              $placeholder_val = @$element['#placeholder'];
              $p_parts = $currency->formattedParts(abs(intval($placeholder_val)));
              $element[$i]['#placeholder'] = $p_parts[$i];
            }
          }
          else {
            //leave the main value field empty, and put zero placeholders in other fields
            $element[$i]['#placeholder'] = $i == 2 ? '' : str_pad('', $size, '0');
          }
        }
        unset($options);
      }
      else {//an even number so render it as markup
        $element[$i]['#markup'] = $component;
      }
    }
    $cardinality = $element['#oneofmany'] ? 'multiple' : 'single';
    $element['#prefix'] = "<span class = \"worth $cardinality\">";
    $element['#suffix'] = "</span>";
  }

  /**
   * {@inheritdoc}
   * How do we stop the values of the child elements overwriting the $output here?
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $output = [];
    if ($input != FALSE && isset($input['curr_id'])) {
      if ($element['#config'] && $input[1] == '') {
        return [];
      }
      $val = Currency::load($input['curr_id'])->unformat($input);

      if ($element['#minus'] && $val > 0) {
        $val = -$val;
      }
      return [
        'curr_id' => $input['curr_id'],
        'value' => $val
      ];
    }
    else {
      return $element['#default_value'];
    }
  }

  /**
   * validate callback
   */
  public static function validate(&$element, FormStateInterface $form_state) {
    $setval = FALSE;
    $val = $element['#value'];
    if (empty($val)) {
      return;
    }
    if ($element['#config']) {
      //test the formula
      if (empty($val['value'])) {
        return;
      }
      $result = Self::calc($val['value'], 100);
      if (!is_numeric($result)) {
        $form_state->setError($element, t('Invalid formula'));
      }
      $setval = TRUE;
    }
    else {
      //check for allowed zero values.
      //zero values are only accepted if the currency allows and if cardinality there is only one currency in the field
      if ($val['value']) {
        if ($val['value'] < 0 && !$element['#minus']) {
          //this should never happen
          $form_state->setError(
            $element['#value']['value'],
            t(
              'Negative amounts not allowed: !val',
              ['%val' => Currency::load($val['curr_id'])->format($worth['value'])]
            )
          );
        }
      }
    }
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
