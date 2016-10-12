<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Mcapi;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\mcapi\Entity\Currency;

/**
 * Provides a worth field form element.
 *
 * @FormElement("worth_form")
 */
class WorthForm extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'process'],
      ],
      '#element_validate' => [
        [$class, 'validate'],
      ],
      '#attached' => ['library' => ['mcapi/mcapi.worth.element']],
      '#attributes' => ['class' => ['worth']],
      '#minus' => FALSE,
      '#config' => FALSE,
      '#min' => 0,
      '#max' => NULL,
      '#allowed_curr_ids' => [],
      '#oneofmany' => FALSE,
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Form element processor callback for 'worth'.
   *
   * Takes the raw #default_values and makes child widgets according to the
   * currency format receives something like this:
   * [
   * '#type' => 'worth_form',
   * '#default_value' => [
   * 'curr_id',
   * 'value'
   * ],
   * '#allowed_curr_ids' => [cc],
   * '#config' => $element['#config'],
   * '#max' => NULL,
   * '#min' => 0,
   * ];.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    if (empty($element['#allowed_curr_ids'])) {
      $element['#allowed_curr_ids'] = array_keys(Currency::loadMultiple());
    }
    // We might need to filter #default_value not in config mode.
    if (count($element['#allowed_curr_ids']) == 1) {
      // @todo remove strtolower
      $currency = Currency::load(strtolower(reset($element['#allowed_curr_ids'])));
      Self::subfields($element, $currency);
    }
    // Multiple choice of currencies, but only showing one widget.
    else {
      $currencies = Currency::loadMultiple($element['#allowed_curr_ids']);
      $element['curr_id'] = [
        '#type' => 'select',
        '#options' => Mcapi::entityLabelList('mcapi_currency', $currencies),
        '#required' => TRUE,
      ];
      $element['value'] = [
        '#type' => 'number',
        '#default_value' => $element['#default_value'],
      ];
      if ($element['#config']) {
        $element['value']['#size'] = 10;
        $element['value']['#maxlength'] = 10;
      }
    }
    return $element;
  }

  /**
   * Build the widget for a single currency.
   *
   * @param array $element
   *   An unprocessed part of a render array.
   * @param Currency $currency
   *   A currency entity.
   */
  public static function subfields(array &$element, Currency $currency) {
    // See #minus.
    $value = abs($element['#default_value']);
    if ($element['#config'] && (!is_numeric($value) || $value == 0)) {
      $value_parts = [];
    }
    else {
      $value_parts = (strlen($value)) ?
        $currency->formattedParts(intval($value)) :
        [];
    }
    $element['curr_id'] = [
      '#type' => 'hidden',
      '#value'  => $currency->id(),
    ];
    if ($element['#minus']) {
      $element[-1] = [
        '#markup' => '-',
        '#weight' => -1,
      ];
    }
    foreach ($currency->format as $i => $component) {
      $element[$i] = ['#weight' => $i];
      // An odd number so render a form element.
      if ($i % 2) {
        $step = 1;
        $options = [];
        // We need to make a dropdown.
        if (strpos($component, '/')) {
          list($component, $divisor) = explode('/', $component);
          $base = $component + 1;
          $step = $base / $divisor;
          for ($j = 0; $j < $divisor; $j++) {
            $v = $j * $step;
            $options[intval($v)] = $v;
          }
        }
        $element[$i] += [
          '#default_value' => @$value_parts[$i],
          '#theme_wrappers' => [],
        ];
        // If a preset value isn't in the $options
        // then we ignore the options and use the numeric sub-widget.
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
          // No effect in opera.
            '#size' => $size,
            '#maxlength' => $size,
            '#attributes' => ['style' => 'width:' . ($size + 1) . 'em;'],
          ];
          if ($i == 1) {
            // For the first part, 000 translates to a max of 999.
            $element[$i]['#max'] = pow(10, strlen($component)) - 1;
          }
          else {
            // For subsequent parts, the $component is the max
            // first component matters only for the num of digits.
            $element[$i]['#max'] = $component;
          }

          if ($element['#config']) {
            // placeholder's only work in config fields.
            $element['#type'] = 'textfield';
            if (isset($element['#placeholder'])) {
              $placeholder_val = @$element['#placeholder'];
              $p_parts = $currency->formattedParts(abs(intval($placeholder_val)));
              $element[$i]['#placeholder'] = $p_parts[$i];
            }
          }
          else {
            // Leave the main value field empty & put zero placeholders in other
            // fields.
            $element[$i]['#placeholder'] = $i == 2 ? '' : str_pad('', $size, '0');
          }
        }
        unset($options);
      }
      // An even number so render it as markup.
      else {
        $element[$i]['#markup'] = $component;
      }
    }
    $cardinality = $element['#oneofmany'] ? 'multiple' : 'single';
    $element['#prefix'] = "<span class = \"worth $cardinality\">";
    $element['#suffix'] = "</span>";
  }

  /**
   * {@inheritdoc}
   *
   * @todo Stop the values of the child elements overwriting the $output here?
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
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
        'value' => $val,
      ];
    }
    else {
      return $element['#default_value'];
    }
  }

  /**
   * Validate callback.
   */
  public static function validate(&$element, FormStateInterface $form_state) {
    $val = $element['#value'];
    if (empty($val)) {
      return;
    }
    if ($element['#config']) {
      // Test the formula.
      if (empty($val['value'])) {
        return;
      }
      $result = Self::calc($val['value'], 100);
      if (!is_numeric($result)) {
        $form_state->setError($element, t('Invalid formula'));
      }
    }
    else {
      // Check for allowed zero values.
      // zero values are only accepted if the currency allows and if cardinality
      // there is only one currency in the field.
      if ($val['value']) {
        if ($val['value'] < 0 && !$element['#minus']) {
          // This should never happen.
          $form_state->setError(
            $element['#value']['value'],
            t(
              'Negative amounts not allowed: !val',
              ['%val' => Currency::load($val['curr_id'])->format($element['#value']['value'])]
            )
          );
        }
      }
    }
  }

  /**
   * Calculate a transaction quantity based on a provided formala and quantity.
   *
   * @param string $formula
   *   Formula using [q] for base_quant. If it is just a number the number is
   *   returned as is. Otherwise [q]% should work or other variables to be
   *   determined.
   * @param int $base_value
   *   The starting value.
   *
   * @return int | NULL
   *   The calculated result.
   */
  public static function calc($formula, $base_value) {
    if (is_null($base_value)) {
      return 0;
    }
    if (is_numeric($formula)) {
      return $formula;
    }
    $proportion = str_replace('%', '', $formula);
    if (empty($base_value)) {
      // @todo there is a problem this variable is not used...
      $base_quant = 0;
    }
    if (is_numeric($proportion)) {
      return $base_value * $proportion / 100;
    }
    // $formula = str_replace('/', '//', $formula);.
    $equation = str_replace('[q]', $base_value, $formula) . ';';
    $val = eval('return ' . $equation);
    if (is_numeric($val)) {
      return $val;
    }
    drupal_set_message(t('Problem with calculation for dependent transaction: @val', array('@val' => $val)));
  }

}
