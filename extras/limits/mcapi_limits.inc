<?php

/*
 * callback for the currency edit form
 * when building the initial page, the $currency is passed, AJAX builds use form_state
 * When called via AJAX, the $settings_form should contain '#value' not '#default_value', not sure why
 */
function limits_settings_subform(&$form, $form_state, $currency) {
  //I'm not very happy about the way the ajax loads are populated by the previous value,
  //but I've been a couple of hours fumbling and we can live with it.
  //the default values are correct even in the form_alter, but on processing,
  //the min/maxfields seem to be populated with their previous value, regardless of the #default_value
  if (isset($form_state['values']['limits']['limits_callback'])) {
    $limits_callback = $form_state['values']['limits']['limits_callback'];
    $defaults = array();
  }
  elseif(isset($currency->limits['limits_callback'])) {
    $limits_callback = $currency->limits['limits_callback'];
    $defaults = isset($currency->limits[$limits_callback]) ? $currency->limits[$limits_callback] : array();
  }
  else {
    $limits_callback = 'limits_none';
    $defaults = array();
  }
  $form_callback = $limits_callback .'_form';
  if (!function_exists($form_callback)) {
    drupal_set_message('Unknown limits settings callback: '.$form_callback);
    return array();
  }
  if (empty($form['limits'][$limits_callback])) {
    $form['limits'][$limits_callback] = array();
  }
  //get the fields
  $form['limits'][$limits_callback] += $form_callback($currency, $defaults);

  $form['limits'][$limits_callback] += array(
    '#prefix' => '<div id="limits-settings">',
    '#suffix' => '</div>',
  );
  //we return the array for the ajax callback but the main form builder has been altered by reference
  return $form['limits'][$limits_callback];
}



/*
 * NO LIMITS
 */
function limits_none($currency, $uid) {
  return array();
}
function limits_none_form($currency, array $defaults) {
  return array();
}


/*
 * GLOBAL LIMITS
 */

//limits callback - returns the min an max limits for that currency for that user
function limits_global($currency, $uid) {
  return $currency->limits['limits_global'];
}
function limits_global_form($currency, array $defaults) {
  if (isset($currency->limits['limits_global'])) {
    $defaults += array_filter($currency->limits['limits_global']);
  }
  return min_max_fields($defaults);
}

/*
 * PERSONAL LIMITS
 */
function limits_personal($currency, $uid) {
  $account = user_load($uid);
  $currcode = $currency->info['currcode'];
  $personal = array();
  if (isset($account->data['limits_personal'][$currcode]) && is_array($account->data['limits_personal'][$currcode])) {
    $personal = $account->data['limits_personal'][$currcode];
  }
  return $personal + $currency->limits['limits_personal'];
}
/*
 * the sub form for setting the default user limits on the currency form
 */
function limits_personal_form($currency, array $defaults) {
  $saved = empty($currency->limits['limits_personal']) ? array('min' => NULL, 'max' => NULL) : $currency->limits['limits_personal'];
  $personal_form = min_max_fields($saved);
  $personal_form['#description'] = t("Users with permission to manage all transactions can edit the users' personal limits.");
  $personal_form['min']['#title'] = t('Default minimum balance.');
  $personal_form['max']['#title'] = t('Default maximum balance.');
  return $personal_form;
}


/*
 * Equation
 * has tokens
 * @gross_in gross income
 * @gross_out gross spending
 * @balance number of trades
 * @trades number of trades
 * e.g. 10+@gross_in+@gross_out+@trades
 */
function limits_equations($currency, $uid) {
  $values = (array)transaction_totals($uid, $currency->info['currcode']);
  sort($values);
  $replacements = array_combine(
    array('@balance', '@gross_in', '@gross_out', '@trades'),//sorted as declared
    $values
  );
  $max_formula = strtr($currency->limits['limits_equations']['max'], $replacements);
  $min_formula = strtr($currency->limits['limits_equations']['min'], $replacements);
  return array(
    'max' => eval('return $max_formula;'),
    'min' => eval('return $min_formula;'),
  );
}

function limits_equations_form($currency, array $defaults) {
  return array(
    'min' => array(
      '#title' => t('Formula to calculate minimum limit'),
      '#description' => t('Use the following tokens:') . ' '. t('@gross_in, @gross_out, @trades'),
      '#type' => 'textfield',
      '#default_value' => isset($defaults['min']) ? $defaults['min'] : '-',
      '#element_validate' => array('mcapi_limits_equation_validate'),
      '#required' => TRUE,
    ),
    'max' => array(
      '#title' => t('Formula to calculate maximum limit'),
      '#description' => t('Use the following tokens:') . ' '. t('@gross_in, @gross_out, @trades'),
      '#type' => 'textfield',
      '#default_value' => isset($defaults['max']) ? $defaults['max'] : '-',
      '#element_validate' => array('mcapi_limits_equation_validate'),
      '#required' => TRUE,
    ),
  );
}

//checks that the formula evaluates to a number
function mcapi_limits_equation_validate(&$element, &$form_state) {
  if (!strlen($element['#value'])) return;
  $value = mcapi_limits_equation_parse(
    $element['#value'],
    array('gross_in' => 100, 'gross_out' => 100, 'trades' => 10)
  );
  if (!is_numeric($value)) {
    form_error($element, t('Formula does not evaluate to a number: @result', array('@result' => $result)));
  }
}
function mcapi_limits_equation_parse($string, $values) {
  $pattern = 'return '. str_replace('@', '', $string) .';';
  $formula = strtr(
    $pattern,
    $values
  );
  return eval($formula);
}

function min_max_fields($defaults) {
  return array(
    'min' => array(
      '#title' => t('Optional minimum balance'),
      '#description' => t('Must be less than or equal to zero'),
      '#type' => 'textfield',
      '#default_value' => isset($defaults['min']) ? $defaults['min'] : '-',
      '#element_validate' => array('mcapi_validate_optional_num', 'mcapi_limits_validate_min'),
      '#size' => 7,
      '#maxlength' => 7,
      '#attributes' => array('style' => 'width:inherit;')
     ),
     'max' => array(
      '#title' => t('Optional maximum balance'),
      '#description' => t('Must be greater than 0.'),
      '#type' => 'textfield',
      '#default_value' => isset($defaults['max']) ? $defaults['max'] : '',
      '#element_validate' => array('mcapi_validate_optional_num', 'mcapi_limits_validate_max'),
      '#size' => 6,
      '#maxlength' => 6,
      '#weight' => 1,
      '#attributes' => array('style' => 'width:inherit;'),
    )
  );
}

function mcapi_validate_optional_num(&$element, $form_state) {
  if ($element['#value'] == '-') {//this is the default field value, meaning minus zero
    form_set_value($element, 0, $form_state);
  }
  if (strlen($element['#value']) && !is_numeric($element['#value'])) {
    form_error($element, t('Limits must be numeric: @value', array('@value' => $element['#value'])));
  }
}

//validate callback subsection of currency form (and sometimes user profile form)
//this could be done in javascript also, on the form
function mcapi_limits_validate_min(&$element, $form_state) {
  if (!empty($element['#value']) && $element['#value'] > 0) {
    form_error($element, t('Minimum balance must not be larger than 0'));
  }
}

//validate callback subsection of currency form (and sometimes user profile form)
//this could be done in javascript also, on the form
function mcapi_limits_validate_max(&$element, $form_state) {
  if (!empty($element['#value']) && $element['#value'] <= 0) {
    form_error($element, t('Maximum balance must be larger than 0'));
  }
}