<?


/*
 * callback for the currency edit form
 * when building the initial page, the $currency is passed, AJAX builds use form_state
 * When called via AJAX, the $settings_form should contain '#value' not '#default_value', not sure why
 */
function limits_settings_subform(&$form, $form_state) {
  $currency = &$form['#currency'];
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
  return array(
    'max' => NULL,
    'min' => NULL,
  );
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
  $sums = transaction_totals($uid, $currency->info['currcode']);
  $formula_max = $currency->limits['limits_equations']['max'];
  $formula_min = $currency->limits['limits_equations']['min'];
  $tokens = array('@gross_in', '@gross_out', '@balance', '@trades');
  $replacements = array(
    $sums['gross_in'], 
    $sums['gross_out'], 
    $sums['balance'], 
    $sums['count'],
  );
  $formula_max = str_replace($tokens, $replacements, $formula_max);
  $formula_min = str_replace($tokens, $replacements, $formula_min);
  return array(
    'max' => eval('return '. $formula_max .';'),
    'min' => eval('return '. $formula_min .';'),
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
    ),
    'max' => array(
      '#title' => t('Formula to calculate maximum limit'),
      '#description' => t('Use the following tokens:') . ' '. t('@gross_in, @gross_out, @trades'),
      '#type' => 'textfield',
      '#default_value' => isset($defaults['max']) ? $defaults['max'] : '-',
      '#element_validate' => array('mcapi_limits_equation_validate'),
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
      '#element_validate' => array('mcapi_limits_validate_minmax'),
      '#size' => 7,
      '#maxlength' => 7,
      '#attributes' => array('style' => 'width:inherit;')
     ),
     'max' => array(
      '#title' => t('Optional maximum balance'),
      '#description' => t('Must be greater than 0.'),
      '#type' => 'textfield',
      '#default_value' => isset($defaults['max']) ? $defaults['max'] : '',
      '#element_validate' => array('mcapi_limits_validate_minmax'),
      '#size' => 6,
      '#maxlength' => 6,
      '#weight' => 1,
      '#attributes' => array('style' => 'width:inherit;'),
    )
  );
}


//validate callback for the min and max values on the currency form
//this could be done in javascript also, on the form
//values have already been validated as numeric
function mcapi_limits_validate_minmax(&$element, $form_state) {
  if (strlen($element['#value']) && !is_numeric($element['#value'])) {
    form_error($element, t('Limits must be numeric: @value', array('@value' => $element['#value'])));
  }
  if ($element['#name'] == 'min' && strlen($element['#value'])){
    if ($element['#value'] > 0) {
      form_error($element, t('Minimum balance must not be larger than 0'));
    }
  }
  elseif ($element['#name'] == 'max' && strlen($element['#value'])){
    if ($element['#value'] <= 0) {
      form_error($element, t('Maximum balance must be larger than 0'));
    }
  }
}


/*
 * callback to return themed balance limits
 */
function show_balance_limits($account, $currcode) {
  $limits = extract(limits_get($currcode, $account->uid));
  extract(transaction_totals($account->uid, $currcode));

  //this chart makes no sense if there isn't a max (or min) limit
  if (!is_numeric($max) || !is_numeric($min)) {
    return array();
  }
  return array(
    '#theme' => 'balance_limits',
    '#currency' => currency_load($currcode),
    '#min' => theme('worth_field', array('currcode' => $currcode, 'quantity' => $min)),
    '#max' => theme('worth_field', array('currcode' => $currcode, 'quantity' => $max)),
    '#balance' => theme('worth_field', array('currcode' => $currcode, 'quantity' => $balance))
  );
}
function show_trading_limits($account, $currcode) {
  extract(transaction_totals($account->uid, $currcode));
  extract(limits_get_adjusted($currcode, $account->uid, $balance));
  return array(
    '#theme' => 'trading_limits',
    '#currency' => currency_load($currcode),
    '#spend_limit' => theme('worth_field', array('currcode' => $currcode, 'quantity' => $spend_limit)),
    '#earn_limit' => theme('worth_field', array('currcode' => $currcode, 'quantity' => $earn_limit)),
  );
}