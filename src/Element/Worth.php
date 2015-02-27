<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Worth.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\mcapi\Entity\Transaction;
//use Drupal\mcapi\Entity\Currency; some wierd problem with this loading twice
use Drupal\Core\Template\Attribute;

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
        array($class, 'process_defaults'),
        array($class, 'process')
      ),
      '#element_validate' => array(
        array($class, 'validate')
      ),
      '#theme_wrappers' => array('form_element'),
      '#attached' => array('library' => array('mcapi/mcapi.worth.element')),
      '#minus' => FALSE,
      '#config' => FALSE,
      '#allowed_curr_ids' => []
    );
    debug('mcapi.worth.element', 'library');
  }

  /**
   * helper function to ensure the worth field is showing the right subset of currencies
   * the #default_value is then used to build the widget
   */
  public static function process_defaults($element, FormStateInterface $form_state, $form) {
    $blank = $element['#config'] ? '' : 0;
    if ($element['#value']) {
      $element['#default_value'] = $element['#value'];
    }
    else {
      //change the all_available array to a worths value array populated by zeros
      if ($allowed_curr_ids = $element['#allowed_curr_ids']) {
        $existing_curr_ids = [];

        foreach ((array)$element['#default_value'] as $item) {
          if (!$item['curr_id']) continue; //means the widget was unpopulated
          $existing_curr_ids[] = $item['curr_id'];
        }
        unset($element['#default_value']);
        //restrict the defaults according to the allowed currencies
        if ($not_allowed = array_diff($existing_curr_ids, $allowed_curr_ids)) {
          //message only shows the FIRST not allowed currency
          drupal_set_message(
            t(
              'Passed default @currency is not one of the allowed currencies',
              array('@currency' => \Drupal\mcapi\Entity\Currency::load(reset($not_allowed))->label())
            ),
            'warning'
          );
        }
        //ensure each allowed currencies has a default value, which is used for building the widget
        foreach (array_diff($allowed_curr_ids, $existing_curr_ids) as $curr_id) {
          //$element['#default_value'][] = array('curr_id' => $curr_id, 'value' => $blank);
        }
      }
      if (empty($element['#default_value'])) {
        drupal_set_message('No currencies have been specified in the worth field.', 'error');
      }
    }
    
    //sort the currencies by weight.
    return $element;
  }

  /**
   * form element processor callback for 'worth'
   * takes the raw #default_values and makes child widgets according to the currency format.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    //we might need to filter #default_value not in config mode
    $worth_cardinality = count($element['#default_value']) > 1 ? 'multiple' : 'single';
    foreach ($element['#default_value'] as $delta => $item) {
      //i want a div around each currency widget
      extract($item);//creates $curr_id and $value

      $currency = \Drupal\mcapi\Entity\Currency::load($curr_id);
      if ($element['#config'] && !is_numeric($value)) $parts = [];
      else $parts = $currency->formatted_parts(abs(intval($value)));
      $element[$delta]['curr_id'] = ['#type' => 'hidden', '#value' => $curr_id];

      $element[$delta]['#type'] = 'container';
      $element[$delta]['#prefix'] = "<div class = \"$worth_cardinality\">";
      $element[$delta]['#suffix'] = '</div>';
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
          $element[$delta][$i] = array(
            '#weight' => $i,
            '#value' => @$parts[$i],//in config mode $parts is empty
            '#theme_wrappers' => []
          );
          //if a preset value isn't in the $options
          //then we ignore the options and use the numeric sub-widget
          if (isset($options) && array_key_exists(@$parts[$i], $options)) {
            //TODO how about using another number field instead of select?
            $element[$delta][$i] += array(
              '#type' => 'select',
              '#options' => $options,
            );
          }
          else {
            $size = strlen($component);
            $element[$delta][$i] += array(
              '#type' => $element['#config'] ? 'textfield' : 'number',
              '#type' => 'textfield',//number was behaving very strangely in alpha 15,
              '#max' => $i == 1 ? pow(10, strlen($component))-1 : $component,//first component matters only for the num of digits
              '#min' => 0,
              '#step' => $step,
              '#size' => $size,//no effect in opera
              '#maxlength' => $size,
            );
            if ($element['#config']) {
              if (array_key_exists('#placeholder', $element)) {
                //debug($element['#placeholder'], $delta);
                $placeholder_val = @$element['#placeholder'][$delta];
                $p_parts = $currency->formatted_parts(abs(intval($placeholder_val)));
                $element[$delta][$i]['#placeholder'] = $p_parts[$i];
              }
            }
            else {
              //leave the main value field empty, and put zero placeholders in other fields
              $element[$delta][$i]['#placeholder'] = $i == 2 ? '' : str_pad('', $size, '0');
            }
          }
          unset($options);
        }
        else {//an even number so render it as markup
          $element[$delta][$i] = array(
            '#weight' => $i,
            '#markup' => $component
          );
        }
      }
      if ($element['#minus']) {
        $element[$delta][0]['#markup'] = '-'.$element[$delta][0]['#markup'];
        $element[$delta][$i]['#suffix'] = '('.t('minus').')';
      }

      if ($element['#config']) {
        static $i = 0; 
        if (!$i)drupal_set_message('tweaking worth widget for config', 'warning', FALSE);
        $i++;
        foreach (element_children($element) as $delta) {
          //this field will accept a formula, not just a number
          $element[$delta][1]['#size'] = 10;
          $element[$delta][1]['#maxlength'] = 10;
        }
      }
    }
    //single values can inherit max and min from the top level of the element
    if (count($element['#default_value']) == 1) {
      if (array_key_exists('#max', $element)) $element[$delta][1]['#max'] = $element['#max'];
      if (array_key_exists('#min', $element)) $element[$delta][1]['#min'] = $element['#min'];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   * How do we stop the values of the child elements overwriting the $output here?
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $output = [];
    if ($input !== FALSE) {
      foreach ($input as $delta => $parts) {
        $curr_id = $parts['curr_id'];
        $quant = \Drupal\mcapi\Entity\Currency::load($curr_id)->unformat($parts);
        if ($element['#config']) {
          //leaving the main value component blank in config mode means ignore the currency
          if (empty($quant)) continue;
        }
        else {
          if (!empty($element['#minus'])) $quant = -$quant;
        }
        //this lets blank items through, to be cleared up during validation, after checking whether $currency->zero permits zero values
        $output[$curr_id] = ['curr_id' => $curr_id, 'value' => $quant];
      }
    }
    else {
      //return the 'default' value - guess that's what is set 
      debug($element['#default_value'], 'check this working');
      foreach ($element['#default_value'] as $delta => $worth) {
        $output[] = ['curr_id' => $worth['curr_id'], 'value' => $worth['value']];
      }
    }
    return $output;
  }

  /**
   * validate callback
   */
  public static function validate(&$element, FormStateInterface $form_state) {
    $setval = FALSE;
    if ($element['#config']) {
      //test the formula
      foreach($element['#value'] as $delta => $worth) {
        if (empty($worth['value'])) continue;
        $result = Transaction::calc($worth['value'], 100);
        if (!is_numeric($result)) {
          $form_state->setError($element[$delta], t('Invalid formula'));
        }
      }
      $setval = TRUE;
    }
    else {
      //check for allowed zero values.
      foreach ($element['#value'] as $delta => $worth) {
        $currency = mcapi_currency_load($worth['curr_id']);
        //zero values are only accepted if the currency allows and if cardinality there is only one currency in the field
        if (empty($worth['value'])) {
          if (count($element['#value']) > 1) {
            //remove zero values from multiple currency transactions
            unset($element['#value'][$delta]);
          }
          elseif (empty($currency->zero)) {
            $form_state->setError($element[$worth['curr_id']], t('Zero value not allowed for this currency.'));
          }
        }
        else {
          if ($worth['value'] < 0 && !$element['#minus']) {
            //TODO check this
            $form_state->setError(
              $element[$worth['curr_id']],
              t('Negative amounts not allowed: !val', array('!val' => $currency->format($worth['value'])))
            );
          }
        }
      }
    }
    $element['#value'] = array_values($element['#value']);
    //reset the element value because the sub-elements have been added to what valueCallback returned
    $form_state->setValue($element['#parents'], $element['#value']);
    $vals = $form_state->getValues();
  }

}
