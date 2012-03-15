<?


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
 * validate callback which checks that the transaction doesn't take anyone beyond their limits
 */
function limits_transaction_form_validate(&$form, $form_state) {
  try {
    mcapi_limit_check($form_state['transaction']);
  }
  catch (Exception $e) {
    form_set_error('quantity', $e->getMessage());
  }
}


/*
 * limit checker
 * checks a transaction to see if the proposed payment will push participant beyond balance limits
 * $transaction = A transaction object
 * $state = Whether to compare with finished or pending balances.
 * This is a drupal function, but the checking could just as easily be done through the entity controller.
 */
function mcapi_limit_check($transaction) {
  reset($transaction->worth);
  $flows = current($transaction->worth);
  foreach ($flows as $delta => $flow) {
    $currcode = $flow['currcode'];
    $difference = $flow['quantity'];
    $payer = $transaction->payer;
    $payee = $transaction->payee;
    $messages = array();
    if ($transaction->state == TRANSACTION_STATE_FINISHED) {
      //the difference is between this value and the saved one
      $saved_flow = current(transaction_load($transaction->xid))->worth;
      $difference -= $saved_flow[$delta]['quantity'];
    }
    //check that the payee isn't going over their max limit
    $limits = limits_get($currcode, $payee);
    if (is_numeric($limits['max'])) {
      $balance = entity_get_controller('transaction')
        ->balance($payee, $currcode, $states = array(TRANSACTION_STATE_FINISHED));
      $payee_projected = $balance + $difference;
      $surplus = $payee_projected - $limits['max'];
      if ($surplus > 0) {
        throw new Exception($payee_projected .'>'.$limits['max']);
      }
    }
    //check that the payer isn't going below their min limit
    $limits = limits_get($currcode, $payer);
    if (is_numeric($limits['min'])) {
      $balance = entity_get_controller('transaction')
        ->balance($payer, $currcode, $states = array(TRANSACTION_STATE_FINISHED));
      $payer_projected = $balance - $difference;
      $deficit = $limits['min'] - $payer_projected;
      if ($deficit > 0) {
        throw new Exception($payer_projected .'<'.$limits['min']);
      }
    }
  }
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
 * @trades number of trades
 * e.g. 10+@gross_in+@gross_out+@trades
 */
function limits_equations($currency, $uid) {
  $cached = transaction_controller('get_cache', $uid, $currency->currcode);
  foreach (array('max', 'min') as $limit) {
    $limits[$limit] = mcapi_limits_equation_parse(
      $currency->limits_equations[$limit],
      db_query("SELECT gross_in, gross_out, count as trades from {mcapi_cache} WHERE uid = $uid AND currcode = $currency->currcode")->fetchAssoc()
    );
  }
  return $limits;
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