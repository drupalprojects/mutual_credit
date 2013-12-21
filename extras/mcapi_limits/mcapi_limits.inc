<?php

use Drupal\Core\Template\Attribute;


function template_preprocess_mcapi_limits_absolute(&$vars) {
  $currency = $vars['currency'];
  $summary = mcapi_account_summary($vars['account'], $currency);
  $vars['data'] = mcapi_limits_plugin($currency)->getLimits($vars['account']);
  $vars['data']['balance'] = $summary['balance'];
  $vars['balance'] = $currency->format($vars['data']['balance']);
  $vars['max'] = $currency->format($vars['data']['max']);
  $vars['min'] = $currency->format($vars['data']['min']);
}
function template_preprocess_mcapi_limits_relative(&$vars) {
  $adjustments = limits_get_adjusted($vars['currency'], $vars['account']);
  $vars['spend_limit'] = $vars['currency']->format($adjustments['spend_limit']);
  $vars['earn_limit'] = $vars['currency']->format($adjustments['earn_limit']);
}
function template_preprocess_mcapi_limits_balanced(&$vars) {
  template_preprocess_mcapi_limits_absolute($vars);
  extract($vars['data']);
  $danger_zone_percent = 25;
  $balance_gauge_pixels = 120;
  $range = $max-$min;
  $zone_size = $range * $danger_zone_percent / 200;

  $min_safe = $min + $zone_size;
  $max_safe = $max - $zone_size;
  $id = $vars['id'] = $vars['currency']->id().'-ometer-'.$vars['account']->id();
  $vars['width'] = 160;
  $vars['height'] = 160;


  $vars['javascript'] = "
function drawGauge() {
  var data = google.visualization.arrayToDataTable([
    ['Label', 'Value'],
    ['". $vars['currency']->label() ."', $balance]
    ]);
    var options = {
    min: ". $min .",max: ". $max .",
    greenColor: '#FF9900', greenFrom: ". $min .", greenTo: ". $min_safe .",
    yellowColor: '#ffffff', yellowFrom:". $min_safe .", yellowTo: ". $max_safe .",
    redColor: '#FF9900', redFrom: ". $max_safe .", redTo: ". $max ."
};
new google.visualization.Gauge(document.getElementById('$id')).draw(data, options);
}
google.setOnLoadCallback(drawGauge);
";
  drupal_add_js('http://www.google.com/jsapi');
  drupal_add_js("google.load('visualization', '1', {packages: ['gauge']});", 'inline');
}


//this shows the amount available to spend or earn, by factoring in the user's balance
//rather than just showing the fixed limits
function limits_get_adjusted($currency, $account) {
  $summary = mcapi_account_summary($account, $currency);
  $limits = mcapi_limits_plugin($currency)->getLimits($account);
  $adjusted = array();
  if (isset($limits['min'])) $adjusted['spend_limit'] = $summary['balance'] - $limits['min'];
  if (isset($limits['max'])) $adjusted['earn_limit'] = $limits['max'] - $summary['balance'];
  return $adjusted;
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
    array('@trades', '@gross_in', '@gross_out', '@balance', '@volume'),//sorted as declared
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


function mcapi_validate_optional_num(&$element, $form_state) {
  if ($element['#value'] == '-') {//this is the default field value, meaning minus zero
    form_set_value($element, 0, $form_state);
  }
  if (strlen($element['#value']) && !is_numeric($element['#value'])) {
    form_error($element, t('Limits must be numeric: @value', array('@value' => $element['#value'])));
  }
}

