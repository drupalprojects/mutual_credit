<?php

//TODO why is NestedArray here? Still needed?
use Drupal\Component\Utility\NestedArray;

/*
 * transaction_access callbacks
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
