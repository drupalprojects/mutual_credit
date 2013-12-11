<?php

use Drupal\Component\Utility\NestedArray;



/*
 * menu page callback
 * builds 'are you sure' operations form for both ajax and nojs
 */
function mcapi_get_op_form($op, $transaction, $mode = 'nojs') {
  drupal_add_js('misc/ajax.js', array('type' => 'file'));
  $operations = transaction_operations();

  if (array_key_exists('form callback', $operations[$op])) {
    $form = drupal_get_form('transaction_operation_form', $op, $transaction);
    //this form will sumit and expect ajax
    $form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
    drupal_add_js('misc/jquery.form.js');
    drupal_add_js('misc/ajax.js', array('type' => 'file'));
  }
  //return normally, or via ajax if called via ajax
  if ($mode == 'nojs') {//return a render array for delivery as a page
    //return an 'are you sure form'
    if (isset($form)) {
      return $form;
    }
    //do the operation directly, without a form, and return the render array
    else {
      return mcapi_submit_op_form($operations[$op], $transaction);
    }
  }
  elseif ($mode == 'ajax') {//return ajax commands
    //when the button is clicked replace the whole transaction div with the results.
    $commands[] = ajax_command_replace('#transaction-'.$transaction->serial, drupal_render($form));
    return array(
      '#type' => 'ajax',
      '#commands' => $commands
    );
  }

}

/*
 * default form callback for all transaction operations
 */
function transaction_operation_form($form, &$form_state, $op, $transaction) {
  $operations = transaction_operations(TRUE, FALSE);
  $info = $operations[$op];
  mcapi_hide_tabs();
  $form['serial'] = array(
    '#type' => 'hidden',
    '#value' => $transaction->serial
  );
  $form_state['transaction_operation'] = $operations[$op]; //pass the op to the submit handler
  if (array_key_exists('form callback', $info) && function_exists($info['form callback'])) {
    $form += $info['form callback']($op, $transaction);
  }
  $form['certificate'] = transaction_view($transaction, 'certificate', TRUE);
  //we might want to write something a bit cleaner than this
  $form = confirm_form(
    $form,
    entity_label('transaction', $transaction),//this page title stays in place throughout the ajax operation
    isset($info['redirect']) ? $info['redirect'] : 'transaction/'. $transaction->serial,
    $info['sure'],
    $info['title'],
    t('Not so sure'),
    $op
  );
  return $form;
}
//this is only called from the are you sure form, and always from ajax
function transaction_operation_form_submit($form, &$form_state) {
  $transaction = transaction_load($form_state['values']['serial']);
  $renderable = mcapi_submit_op_form($form_state['transaction_operation'], $transaction, $form_state['values']);
  //if this is ajax we return the result, otherwise redirect the form
  $commands[] = ajax_command_replace('#transaction-operation-form', drupal_render($renderable));
  ajax_deliver(array(
    '#type' => 'ajax',
    '#commands' => $commands
  ));
  exit();
}

//this is called in operation form submission OR ajax
function mcapi_submit_op_form($operation, $transaction, $values = array()) {
  $result = array();
  $old_state = $transaction->state;
  $function = $operation['submit callback'];
  //this changes the $transaction
  $result = $function($operation, $transaction, $values) or 'operation returned nothing renderable';
  if ($operation['mail']) {
    //here we are just sending one mail, in one language
    global $language;
    $to = implode(user_load($transaction->payer)->mail, user_load($transaction->payee)->mail);
    $params['transaction'] = $transaction;
    $params['config'] = $this->configFactory->get('mcapi.operation.'.$operation['op']);
    drupal_mail('mcapi', 'operation', $to, $language->language, $params);
  }
  //and invoke trigger and rules using the changed transaction
  transaction_update($operation['op'], $transaction, $old_state);
  return $result;
}


/*
 *  access callback for transaction operation 'view'
 */
function mcapi_view_access($op, $transaction, $currency) {
  return _transaction_check_access_callbacks(
    $currency->view_transaction_states[$transaction->state->value],
    $transaction
  );
}

/*
 * access callback for transaction operation 'undo'
 * Undo is the only 'exposed' operation in this module
 */
function transaction_op_undo_access($op, $transaction, $currency) {
  if ($transaction->state->value != TRANSACTION_STATE_FINISHED) return FALSE;
  return _transaction_check_access_callbacks($currency->access_operations[$op], $transaction);
}

function transaction_op_undo($operation, $transaction, $values) {
  transaction_undo($transaction->serial);
  $message = t('The transaction is undone.') .' ';
  return array('#markup' => $message);
}

/*
 * helper function to run through all the access callbacks after the operation's own access callback has done its tests
 */
function _transaction_check_access_callbacks($callbacks, $transaction) {
  foreach ($callbacks as $function) {
    if ($function($transaction)) return TRUE;
  }
  return FALSE;
}

/*
 * transaction_access callbacks
 * $ops are determined in hook_transaction_operations
 */
function transaction_access_callback_perm_transact() {
  return user_access('transact');
}
function transaction_access_callback_perm_manage_all() {
  return user_access('manage all transactions');
}
function transaction_access_callback_payee($transaction) {
  return \Drupal::currentUser()->id() == $transaction->payee->value;
}
function transaction_access_callback_payer($transaction) {
  return \Drupal::currentUser()->id() == $transaction->payer->value;
}
function transaction_access_callback_open() {
  return TRUE;
}
function transaction_access_callback_creator($transaction) {
  return \Drupal::currentUser()->id() == $transaction->creator;
}

/*
 * action callback for hook_transaction_children
 * //TODO This goes with RULES
 */
function mcapi_dependent_transaction_action($transaction, $settings) {
  if (!in_array($transaction->type, $settings['conditions']['transaction_types'])) return;
  $child = array(
    'type' => 'dependent',
    'state' => $transaction->state
  );

  foreach ($settings['presets']['worth_items']['worths'] as $delta => $item) {
    //if this currency was in the prime transaction, pass it to the calculator
    $calculated = _mcapi_calc($item['quantity'], @$transaction->worth[LANGUAGE_NONE][$delta]['quantity']);
    if (!$calculated) continue;
    $rounded = mcapi_round($calculated, $item['currcode'], $settings['presets']['worth_items']['round'] == 'up');
    //don't save zero value auto transactions, even if the currency settings permit
    if ($rounded == 0) continue;
    //if a quant was returned (and there really should be, from at least one currency), add it to the $dependent
    $child['worth'][$delta] = array(
      'quantity' => $rounded,
      'currcode' => $item['currcode']
    );
  }
  if (!isset($child['worth'])) return;

  $otheruser = &$settings['presets']['participants']['otheruser'];
  switch ($settings['presets']['participants']['mapping']) {
    case 'payerpays':
      $child['payee'] = $otheruser;
      $child['payer'] = $transaction->payer;
      break;
    case 'payeepays':
      $child['payee'] = $otheruser;
      $child['payer'] = $transaction->payee;
      break;
    case 'payspayer':
      $child['payee'] = $transaction->payer;
      $child['payer'] = $otheruser;
      break;
    case 'payspayee':
      $child['payee'] = $transaction->payee;
      $child['payer'] = $otheruser;
      break;
  }
  $child['description'] = @$settings['presets']['description'];
  return entity_create('transaction', $child);
}


/*
 * calculate a transaction quantity based on a provided formala and input quantity
 * $formula can be
 * number => returns the number as a quantity, regardless of $base_quant
 * percentage of the $base_quant
 * formula using [q] for base_quant
 */
function _mcapi_calc($formula, $base_value) {
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


/*
 * Calculate the balance changes that this transaction proposes
 * by convention, if the transaction state < 0 it is NOT COUNTED
 * this is only used in tokens, so far, and in mcapi_limits module
 * incoming transaction can be a transaction object with children or an array
 */
function _transactions_diff($transaction) {
  $diff = array();
  //because this can be called before or after the transaction,
  //it might come in as a cluster, or as an object with dependents
  //we need a cluster so we can iterate though
  $cluster = is_array($transaction) ? $transaction :  array_merge(array($transaction) + (array)$transaction->dependents);
  foreach ($cluster as $transfer) {
    foreach ($transfer->worth[LANGUAGE_NONE] as $item) {
      $quant = $transfer->state < -1 ? 0 : $item['quantity'];//i is signature module is enabled, pending transactions count here
      //we can't prepare the array in advance with zeros so += and -= throws notices
      //instead we just build up an array and add them up when we need them
      $diff[$transfer->payer][$item['currcode']][] = -$quant;
      $diff[$transfer->payee][$item['currcode']][] = $quant;
    }
  }
  return $diff;
}

//this will adjust the rendering of zero worth in accordance with the currency display zero property
//it is not used every time a 'worth' is rendered, but only when transactions are displayed.
//for this reason, though it seems inelegant, this function should be called from wherever the transaction is rendered
//I can't remember the difference. this is called from transaction_preprocess and from the views field
function mcapi_worth_item_zero_adjustment(&$worth_render_array) {
	skdjfhk();
  $currencies = currencies_load();
  if ($worth_render_array['#quantity'] == 0) {
    $worth_render_array = array('#markup' => $currencies[$worth_render_array['#currcode']]->display['zero']);
  }
}


/*
 * default theme implementation for callback 'worth_element'
 * called once per $item
 */

use Drupal\Core\Template\Attribute;
function theme_worth_element($variables) {
  $element = &$variables['element'];
  $element['#attributes']['class'][] = "worth-widget";
  $element['#attributes'] = new Attribute($element['#attributes']);
  $curr_select = drupal_render($element['currcode']);
  $quant_field = drupal_render($element['main_quant']);
  if ($element['currcode']['#type'] == 'hidden' || $element['currcode']['#theme'] == 'hidden') {//if the currency is preset, then we can use its own display format
    $currcode = isset($element['#value']) ? $element['#value']['currcode'] : $element['#default_value']['currcode'];
    $currency_display = entity_load('mcapi_currency', $currcode)->display;
    if (isset($element['div_quant'])) {
      $quant_field .= $currency_display['delimiter'] . drupal_render($element['div_quant']);
    }
    $output = $curr_select . $quant_field;
  }
  else {//if there is no known currcode, the
    if (!empty($element['div_quant'])) {
      $quant_field .= drupal_render($element['div_quant']);
    }
    $output = $curr_select . $quant_field;
  }
  return '<div ' . $element['#attributes'] . '>'. $output .'</div>';
}


/*
 * a nice display of user balances in a currency
 */
function mcapi_user_summary($account, $currcodes = array()) {
  if (empty($currcodes)) {
    //TODO what's the current way to load all currencies for a user?
    $currcodes = mcapi_get_available_currencies($account);
  }

  $render_array = array();
  foreach (entity_load_multiple('mcapi_currency', array_keys($currcodes)) as $currcode => $currency) {
    if ($currency->access('trader_data')) {
      $render_array[$currcode] = array(
        '#type' => 'mcapi_summary',
        '#currency' => $currency,
        '#account' => $account
      );
    }
  }

  return $render_array;
}

/*
 * rounds a transaction quantity up or down, according to currency settings
 */
function mcapi_round($quant, $currcode, $up = FALSE) {
  $currency = entity_load('mcapi_currency', $currcode);
  switch ($currency->display['widget']) {
    case CURRENCY_WIDGET_SELECT:
      //calculate the number of hundredths from each division to the fraction, and choose the smallest
      list($int, $fraction) = array_pad(explode('.', $quant), 2, 0);
      //divisions allowed exists in loaded currencies only
      foreach ($currency->display['divisions_allowed'] as $division) {
        $diffs[$division] = abs($fraction - $division);
      }
      asort($diffs);
      return "$int.". key($diffs);
      break;
    case CURRENCY_WIDGET_SINGLEFIELD:
      $quant = round($quant, 2);
    case CURRENCY_WIDGET_TEXT:
      return $quant;
  }
}

/*
 * callback to give times and balances
* $mode can be either
*   diagonal, square, or the number of points required after sampling
*/
function get_balance_history($account, $currcodes = array(), $since = NULL) {
	if (empty($currcodes)) {
		$currencies = mcapi_get_available_currencies($account);
	}
	else {
		foreach ($currcodes as $currcode) {
			$currency = mcapi_currency_load($currcode);
			if ($currency->access('trader data', \Drupal::currentUser()) || $account->id() == \Drupal::currentUser()->id()) {
  		  $currencies[$currcode] = $currency;
		  }
		}
	}
	if (empty($currencies)) return;
	$histories = array();
	$storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');
	foreach ($currencies as $currcode => $currency) {
		$history = array($account->created->value => 0);
		$history += $storage->timesBalances($account, $currency);
		if (count($history) > 1) {
			//make a straight line until now
			$history[REQUEST_TIME] = end($history);
			$histories[$currcode] = $history;
		}
		//ignore histories with no transactions
	}
	return $histories;
}

//modify the history points for aesthetic reasons, or to save resources
function template_preprocess_balance_histories_gchart(&$vars) {
	$histories = get_balance_history($vars['account'], $vars['currcodes']);
	foreach ($histories as $currcode => &$history) {
		$point_count = count($history);
		if ($point_count < $vars['width'] / 3) {//step method, for a small number of points
			$times = $values = array();
			//make two values for each one in the keys and values
			foreach ($history as $time => $bal) {
				$times[] = $time;
				$times[] = $time+1;
				$values[] = $bal;
				$values[] = $bal;
			}
			//now slide the arrays against each other
			array_pop($values);
			array_shift($times);
			$history = array_combine($times, $values);
		}
		elseif ($point_count > $vars['width']) {//decimate the array, for a large number of points
			//we can assume that $max_size(1 point per pixes) is smaller than $count
			$ratio = $vars['width'] / $point_count;
			//how to turn this into a fraction involving 1?
			$reciprocal = 1/$ratio;
			$factor = intval($reciprocal + 1);
			//now iterate through the array taking 1 out of every $factor values
			$i = 0;
			foreach($history as $key => $value) {
				if ($i % $factor != 0) unset($history[$key]);
				$i++;
			}
		}
		$vars['histories'][$currcode] = $history;
	}
}


//produce and include the actual javascript
//TODO cache this and clear the cache
//theme preprocessor
function mcapi_preprocess_balance_histories_gchart(&$vars) {
	$histories = &$vars['histories'];
	$id = 'uid-'.$vars['account']->id().'-'.implode('-',array_keys($histories));
	$vars['id'] = $id;
	foreach ($histories as $currcode => $history) {
		$colors[] = "'".mcapi_currency_load($currcode)->color ."'";
		foreach ($history as $timestamp => $balance) {
			$timeline[$timestamp][$currcode] = $balance;
		}
	}
	if (empty($timeline))return '';
	//$timeline is now a list of times and changes of balance in currencies
	ksort($timeline);
	//what we need is a list of times with both balances per moment
	//starting with a default 'prev value
	foreach (array_keys($histories) as $currcode) {
		$prev[$currcode] = 0;
	}
	foreach ($timeline as $timestamp => $balances) {
		$vals = array_merge($prev, $balances);
		$timeline[$timestamp] = $vals;
		$prev = $timeline[$timestamp];
	}
	$vars['height'] = 3* $vars['width']/4;
	$js = "
function drawBalanceHistory() {
  data.addColumn('date', 'Date');";
	foreach (array_keys(current($timeline)) as $currcode) {
		$currname = entity_load('mcapi_currency', $currcode)->name;
	  $js .= "\n  data.addColumn('number', '$currname')";
	}
	foreach ($timeline as $timestamp => $balances) {
		//this has a resolution of one day, not very satisfying perhaps
	  $date = "new Date('".date('m/d/Y', $timestamp)."')";
	  $balances = implode(', ', $balances);
	  $js .= "\n  data.addRow([$date, $balances]);";
	}
	$colors = implode(', ', $colors);
	$js .= "
  var options = {
    curveType: 'function',
    width: ".$vars['width'].",
    height: ".$vars['height'].",
    colors: [$colors],
    legend: {position: 'none'}
  }
  new google.visualization.LineChart(document.getElementById('$id')).draw(data, options);
}";
	//
//	drupal_add_js('http://www.google.com/jsapi');
	drupal_add_js("google.load('visualization', '1', {packages: ['corechart']});\ngoogle.setOnLoadCallback(drawBalanceHistory);", 'inline');
	$vars['javascript'] = $js;
}
