<?php

_drupal_add_css(drupal_get_path('module', 'mcapi').'/css/wallets.css');

/**
 * implements template_preprocess_THEME
 * modify the history points for aesthetic reasons, or to save resources
 * @todo could these long functions go in another file?
 */
function template_preprocess_wallet_history(&$vars) {
  $storage = \Drupal::entityManager()->getStorage('mcapi_transaction');
  if ($history = $storage->timesBalances($vars['wallet']->id(), $vars['currency']->id())) {
    //add a final point showing the balance at this moment
    $history[REQUEST_TIME] = end($history);
  }
  //apply smoothing, or even roughing.
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
    //now slide the arrays against each other to create steps
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
  $vars['history'] = $history;
}



/**
 * implements hook_preprocess_THEMEHOOK for wallet_history
 * generates the javascript for the gchart from the user's history of each currency
 * TODO cache this and clear the cache
 */
function mcapi_preprocess_wallet_history(&$vars) {
  mcapi_add_gchart_js('corechart');
  $currency = $vars['currency'];
  $vars['height'] = $vars['width']*3/4;
  $vars['functionname'] = 'drawHistory'.$currency->id();

  $vars['id'] = 'wallet-'.$vars['wallet']->id().'-'.$currency->id();
  if ($vars['history']) {
    list($min, $middle, $max) = _mcapi_history_axes($vars['history']);
  }
  else {
    $min = -100;
    $middle = 0;
    $max = 100;
  }
  $vars['vaxislabels'] = array(
    array('value' => $min, 'label' => $currency->format($min, TRUE)),
    array('value' => $middle, 'label' => $currency->format($middle, TRUE)),
    array('value' => $max, 'label' => $currency->format($max, TRUE))
  );

  //set up the javascript data object
  $vars['data'] = "
  var data = new google.visualization.DataTable();
  data.addColumn('date', 'Date');
  data.addColumn('number', '".$currency->label()."')
  data.addColumn({type:'string', role:'tooltip'})";

  //populate the javascript data object
  foreach ($vars['history'] as $timestamp => $balance) {
    $balance .= ', "'.$currency->format($balance, TRUE) .'"';
    //this has a resolution of one day, not very satisfying perhaps
    $date = date('m/d/Y', $timestamp);
    $vars['data'] .= "\n  data.addRow([new Date('$date'), $balance]);";//note that $balance is actually 2 items in the row
  }
}


/**
 * theme preprocessor for 'wallet_stats'
 * @todo this theme callback is cached - when to clear the cache and what cache is it?
 */
function template_preprocess_wallet_stats(&$vars) {
  foreach ($vars['wallet']->getSummaries() as $curr_id => $data) {
    $currency = mcapi_currency_load($curr_id);
    $vars['currdata'][$curr_id]['label'] = $currency->label();
    $vars['currdata'][$curr_id]['gross_in'] = $currency->format($data['gross_in'], TRUE);
    $vars['currdata'][$curr_id]['gross_out'] = $currency->format($data['gross_out'], TRUE);
    $vars['currdata'][$curr_id]['volume'] = $currency->format($data['volume'], TRUE);
    $vars['currdata'][$curr_id]['balance'] = $currency->format($data['balance'], TRUE);
    $vars['currdata'][$curr_id]['partners'] = $data['partners'];
    $vars['currdata'][$curr_id]['trades'] = $data['trades'];
  }
  $vars['url'] = url('wallet/'.$vars['wallet']->id());
  $vars['name'] = $vars['wallet']->get('name')->value;
}


/**
 * theme preprocessor for 'wallet_summary'
 * show the most basic stats for one currency
 * @todo this theme callback is cached - when to clear the cache and what cache is it?
 */
function template_preprocess_wallet_summary(&$vars) {
  $vars['url'] = url('wallet/'.$vars['wallet']->id());
  $vars['name'] = $vars['wallet']->get('name')->value;
  //TODO would be nice to sort the $vars['currencies'] in order of the number of trades
  //however can't do that because the currencies haven't been preprocessed yet
  //would need a quite different approach
}

function template_preprocess_wallet_summary_currency(&$vars) {
  $vars['url'] = url('wallet/'.$vars['wallet']->id().'/'.$vars['currency']->id());
  $vars += $vars['wallet']->getStats($vars['currency']->id());
  $vars['balance'] = $vars['currency']->format($vars['balance'], TRUE);
}

/**
 * theme implementation function for wallet_summaries
 */
function template_preprocess_wallets(&$vars) {
  //are the wallets aready themed?
  foreach ($vars['wallets'] as $wid =>  $wallet) {
    $vars['wallets'][$wid] = drupal_render($wallet);
  }
}

/**
 * theme preprocessor for wallet_balance_bars
 */
function template_preprocess_wallet_balance_bars(&$vars) {
  $data = $vars['data'];
  $currency = $vars['currency'];
  //if ($vars['data']['gross_in'] == 0 && $vars['data']['gross_out']) return;
  $vars['id'] = "given-gotten-".$currency->id().'-'.$vars['wallet']->id();
  $vars['functionname'] = str_replace('-', '_', $vars['id']);
  $vars['incoming'] = $data['gross_in'];
  $vars['outgoing'] = $data['gross_out'];
  $vars['show_in'] = $currency->format($data['gross_in'], TRUE);
  $vars['show_out'] = $currency->format($data['gross_out'], TRUE);
  $max = _mcapi_get_axis_max(max($data['gross_in'], $data['gross_out']));
  $vars['max'] = $max;
  $vars['vaxislabels'] = array(
    array('value' => 0, 'label' => $currency->format(0, TRUE)),
    array('value' => $max/2, 'label' => $currency->format($max/2, TRUE)),
    array('value' => $max, 'label' => $currency->format($max), TRUE)
  );
  mcapi_add_gchart_js('corechart');
}


/**
 * add the gchart libs, but only once
 * @param string $lib
 */
function mcapi_add_gchart_js($lib) {
  static $done = array();
  if (!count($done)) {
    _drupal_add_js('http://www.google.com/jsapi');
  }
  if (!array_search($lib, $done)) {
    _drupal_add_js("google.load('visualization', '1', {packages: ['$lib']});", 'inline');
    $done[] = $lib;
  }
}

function _mcapi_history_axes($vals) {
  $max = max($vals);
  $min = min($vals);
  if ($min >= 0) {
    $max = _mcapi_get_axis_max($max);
    return array(0, $max/2, $max);
  }
  elseif ($max <= 0) {
    $min = -_mcapi_get_axis_max(abs($min));
    return array($min, $min/2, 0);
  }
  else {
    return array(-_mcapi_get_axis_max(abs($min)), 0, mcapi_get_axis_max($max));
  }
}


function _mcapi_get_axis_max($val) {
  $scale = array(1, 2, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 10000, 25000, 50000, 100000, 250000, 500000, 1000000);
  $scale[] = $val;
  sort($scale);
  return $scale[array_search($val, $scale)+1];
}
