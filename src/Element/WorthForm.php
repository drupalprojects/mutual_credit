<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\WorthForm.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\mcapi\Entity\Currency as CurrencyEntity;
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
      '#minus' => FALSE,
      '#config' => FALSE,
      '#min' => 0,
      '#max' => NULL,
      '#allowed_curr_ids' => []
    ];
  }

  /**
   * form element processor callback for 'worth'
   * takes the raw #default_values and makes child widgets according to the currency format.
   * receives something like this:
   * [
        '#type' => 'worth_form',
        '#default_value' => 3600,
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
      $currency = CurrencyEntity::load(reset($element['#allowed_curr_ids']));
      if ($element['#config'] && !is_numeric($element['#default_value'])) {
        $value_parts = [];
      }
      else {
        $value_parts = (strlen($element['#default_value'])) ?
          $currency->formattedParts(intval($element['#default_value'])) :
          [];
      }
      Self::subfields($element, $currency, $value_parts);
    }
    else {//this would be if cardinality was 1 but multiple currencies available

      $currencies = CurrencyEntity::loadMultiple($element['#allowed_curr_ids']);
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
    $element['#type'] = 'container';//if necessary
    //consider moving this to the css
    $element['#attributes'] = ['style' => 'display:inline-block'];
    return $element;
  }

  static function subfields(&$element, $currency, $value_parts) {
    $element['curr_id'] = [
      '#type' => 'hidden',
      '#value'  => $currency->id()
    ];
    foreach ($currency->format as $i => $component) {

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
        $element[$i] = array(
          '#weight' => $i,
          '#default_value' => @$value_parts[$i],
          '#theme_wrappers' => []//try removing this
        );
        //if a preset value isn't in the $options
        //then we ignore the options and use the numeric sub-widget
        if (isset($options) && array_key_exists(@$value_parts[$i], $options)) {
          $element[$i] += array(
            '#type' => 'select',
            '#options' => $options,
          );
        }
        else {
          $size = strlen($component);
          $element[$i] += array(
            '#type' => 'number',
            //'#type' => 'textfield',//number was behaving very strangely in alpha 15,
            '#max' => $i == 1 ? pow(10, strlen($component))-1 : $component,//first component matters only for the num of digits
            '#min' => 0,
            '#step' => $step,
            '#size' => $size,//no effect in opera
            '#maxlength' => $size,
            '#attributes' => ['style' => 'width:'.($size +1) .'em;']
          );
          if ($element['#config']) {
            $element['#type'] = 'textfield';
            //@todo markup
            if (array_key_exists('#placeholder', $element)) {
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
        //@todo markup
        $element[$i] = array(
          '#weight' => $i,
          '#markup' => is_array($component) ? $component[0] : $component, //@todo component is never an array
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   * How do we stop the values of the child elements overwriting the $output here?
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $output = [];
    if ($input != FALSE) {
      if ($element['#config'] && $input[1] == '') {
        return;
      }
      $val = CurrencyEntity::load($input['curr_id'])->unformat($input);
      if ($val == 0) {
        return [];
      }

      if ($element['#minus']) {
        $val = -$val;
      }
      return [
        'curr_id' => $input['curr_id'],
        'value' => $val
      ];
    }
    else return $element['#default_value'];
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
      $currency = CurrencyEntity::load($val['curr_id']);
      //zero values are only accepted if the currency allows and if cardinality there is only one currency in the field
      if ($val['value'] == '0') {
        if (empty($currency->zero)) {
          $form_state->setError($element, t('Zero value not allowed.'));
        }
      }
      else {
        if ($val['value'] < 0 && !$element['#minus']) {
          //this should never happen
          $form_state->setError(
            $element['#value']['value'],
            t('Negative amounts not allowed: !val', array('%val' => $currency->format($worth['value'])))
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
