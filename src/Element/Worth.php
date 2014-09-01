<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Worth.
 */

namespace Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a worth field form element.
 *
 * @FormElement("worth")
 */
class Worth extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#tree' => TRUE,
      '#process' => array(
        array($class, 'mcapi_worth_element_process_defaults'),
        array($class, 'mcapi_worth_element_process')
      ),
      '#element_validate' => array(
        array($class, 'mcapi_worth_element_validate')
      ),
      '#theme_wrappers' => array('form_element'),
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'mcapi') . '/css/worth-element.css',
        )
      ),
      '#minus' => FALSE,
      '#config' => FALSE,
      //if this is empty it will be ignored
      //otherwise currencies not in #default values will appear in the form
      '#allowed_curr_ids' => array(),
    );
  }

  public static function mcapi_worth_element_process_defaults($element, FormStateInterface $form_state, $form) {
    $blank = $element['#config'] ? '' : 0;
    //change the all_available array to a worths value array populated by zeros
    if ($allowed_curr_ids = $element['#allowed_curr_ids']) {
      $existing_curr_ids = array();
      foreach ((array)$element['#default_value'] as $item) {
        $existing_curr_ids[] = $item['curr_id'];
      }
      if ($not_allowed = array_diff($existing_curr_ids, $allowed_curr_ids)) {
        //only shows the FIRST not allowed currency
        drupal_set_message(t(
        'Passed default @currency is not one of the allowed currencies',
        array('@currency' => Currency::load(reset($not_allowed))->label())),
        'warning'
            );
      }
      foreach ($add = array_diff($allowed_curr_ids, $existing_curr_ids) as $curr_id) {
        $element['#default_value'][] = array('curr_id' => $curr_id, 'value' => $blank);
      }
    }
    if (empty($element['#default_value'])) {
      drupal_set_message('No currencies have been specified in the worth field.', 'error');
    }
    //TODO sort the currencies by weight.
    return $element;
  }

  public static function mcapi_worth_element_process($element, FormStateInterface $form_state, $form) {

    //we might need to filter #default_value not in config mode
    $worth_cardinality = count($element['#default_value']) > 1 ? 'multiple' : 'single';

    foreach ($element['#default_value'] as $delta => $item) {
      //i want a div around each currency widget
      extract($item);//creates $curr_id and $value

      $currency = mcapi_currency_load($curr_id);
      if ($element['#config'] && !is_numeric($value)) $parts = array();
      else $parts = $currency->formatted_parts(abs(intval($value)));

      $element[$curr_id]['#type'] = 'container';
      $element[$curr_id]['#prefix'] = "<div class = \"$worth_cardinality\">";
      $element[$curr_id]['#suffix'] = '</div>';

      foreach ($currency->format as $i => $component) {
        if ($i % 2) { //an odd number so render a field
          $step = 1;
          if (strpos($component, '/')) {//we need to make a dropdown
            list($component, $divisor) = explode('/', $component);
            $base = $component + 1;
            $step = $base / $divisor;
            for ($j=0; $j < $divisor; $j++) {
              $v = $j* $step;
              $options[intval($v)] = $v;
            }
          }
          $element[$curr_id][$i] = array(
              '#weight' => $i,
              '#value' => @$parts[$i],//in config mode $parts is empty
              '#theme_wrappers' => array()
          );
          //if a preset value isn't in the $options
          //then we ignore the options and use the numeric sub-widget
          if (isset($options) && array_key_exists(@$parts[$i], $options)) {
            $element[$curr_id][$i] += array(
              '#type' => 'select',
              '#options' => $options
            );
          }
          else {
            $size = strlen($component);
            $element[$curr_id][$i] += array(
              '#type' => $element['#config'] ? 'textfield' : 'number',
              '#max' => $i == 1 ? pow(10, strlen($component))-1 : $component,//first component matters only for the num of digits
              '#min' => 0,
              '#step' => $step,
              '#size' => $size,//no effect in opera
              '#max_length' => $size,
              //the size needs to be larger because the widget spinners take up space
              //TODO find out what's going on with the browsers. We want the number field for its validation but the spinners are really bad
              '#attributes' => array('style' => 'width:'. ($size) .'em;'),
            );
            if ($element['#config']) {
              if (array_key_exists('#placeholder', $element)) {
                $placeholder_val = $element['#placeholder'][$delta];
                $p_parts = $currency->formatted_parts(abs(intval($placeholder_val)));
                $element[$curr_id][$i]['#placeholder'] = $p_parts[$i];
              }
            }
            else {
              //leave the main value field empty, and put zero placeholders in other fields
              $element[$curr_id][$i]['#placeholder'] = $i == 2 ? '' : str_pad('', $size, '0');
            }
          }
        }
        else {//an even number so render it as markup
          $element[$curr_id][$i] = array(
              '#weight' => $i,
              '#markup' => $component
          );
        }
      }
      if ($element['#minus']) {
        $element[$curr_id][0]['#markup'] = '-'.$element[$curr_id][0]['#markup'];
        $element[$curr_id][$i]['#suffix'] = '('.t('minus').')';
      }
    }
    //single values can inherit max and min from the top level of the element
    if (count($element['#default_value']) == 1) {
      if (array_key_exists('#max', $element)) $element[$curr_id][1]['#max'] = $element['#max'];
      if (array_key_exists('#min', $element)) $element[$curr_id][1]['#min'] = $element['#min'];
    }
    return $element;
  }

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) return;
    $output = array();
    foreach ($input as $curr_id => $parts) {
      if ($element['#config'] && reset($parts) === ''){
        //leaving the main value component blank in config mode means ignore the currency
        continue;
      }
      $currency = mcapi_currency_load($curr_id);
      $quant = $currency->unformat($parts);
      if ($quant == 0 && !$element['#config']) {
        //zero values are only accepted if the currency allows and if there is only one currency in the field
        if (empty($currency->zero) || count($input) > 1){
          continue;//don't add this value to the $output
        }
      }
      if (!empty($element['#minus'])) $quant = -$quant;
      $output[] = array('curr_id' => $curr_id, 'value' => $quant);
    }
    //be aware that the child widgets will add to this and then be cleaned up in mcapi_worth_element_validate
    return $output;
  }
}
