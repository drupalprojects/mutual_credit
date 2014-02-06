<?php


/**
 * implements template_preprocess_THEME
 * modify the history points for aesthetic reasons, or to save resources
 * @todo could these long functions go in another file?
 *
 * @param array $vars
 */
function template_preprocess_wallet_history(&$vars) {
  $storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');
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
 *
 * @param array $vars
 */
function mcapi_preprocess_wallet_history(&$vars) {
  mcapi_add_gchart_js('corechart');
  $vars['height'] = $vars['width']*3/4;
  $vars['functionname'] = 'drawHistory'.$vars['currency']->id();

  $vars['id'] = 'wallet-'.$vars['wallet']->id().'-'.$vars['currcode'];
  list($min, $middle, $max) = _mcapi_history_axes($vars['history']);
  $vars['vaxislabels'] = array(
    array('value' => $min, 'label' => $vars['currency']->format($min)),
    array('value' => $middle, 'label' => $vars['currency']->format($middle)),
    array('value' => $max, 'label' => $vars['currency']->format($max))
  );

  //set up the javascript data object
  $vars['data'] = "
  var data = new google.visualization.DataTable();
  data.addColumn('date', 'Date');
  data.addColumn('number', '".$vars['currency']->label()."')
  data.addColumn({type:'string', role:'tooltip'})";

  //populate the javascript data object
  foreach ($vars['history'] as $timestamp => $balance) {
    $balance .= ', "'.$vars['currency']->format($balance) .'"';
    //this has a resolution of one day, not very satisfying perhaps
    $date = date('m/d/Y', $timestamp);
    $vars['data'] .= "\n  data.addRow([new Date('$date'), $balance]);";//note that $balance is actually 2 items in the row
  }
}


/**
 * theme preprocessor for 'wallet_stats'
 * @todo this theme callback is cached - when to clear the cache and what cache is it?
 * @param array $vars
 */
function template_preprocess_wallet_stats(&$vars) {
  $currdata = \Drupal::entityManager()
    ->getStorageController('mcapi_transaction')
    ->summaryData($vars['wallet']->id(), array());

  foreach ($currdata as $currcode => $data) {
    $currency = mcapi_currency_load($currcode);
    $vars['currdata'][$currcode]['label'] = $currency->label();
    $vars['currdata'][$currcode]['gross_in'] = $currency->format($data['gross_in']);
    $vars['currdata'][$currcode]['gross_out'] = $currency->format($data['gross_out']);
    $vars['currdata'][$currcode]['volume'] = $currency->format($data['volume']);
    $vars['currdata'][$currcode]['balance'] = $currency->format($data['balance']);
    $vars['currdata'][$currcode]['partners'] = $data['partners'];
    $vars['currdata'][$currcode]['trades'] = $data['trades'];
  }
  $vars['url'] = url('wallet/'.$vars['wallet']->id());
  $vars['name'] = $vars['wallet']->get('name')->value;
}


/**
 * theme preprocessor for 'wallet_summary'
 * @todo this theme callback is cached - when to clear the cache and what cache is it?
 * @param array $vars
 */
function template_preprocess_wallet_summary(&$vars) {
  $vars['balance'] = $vars['currency']->format($vars['data']['balance']);
  $vars['trades'] = $vars['data']['trades'];
  $vars['url'] = url('wallet/'.$vars['wallet']->id());
  $vars['name'] = $vars['wallet']->get('name')->value;

}

function theme_wallets_summaries(&$vars) {
  //are the wallets aready themed?
  foreach ($vars['wallets'] as $wallet) {
    $output = drupal_render($wallet);
  }
  return '<div class = "all-wallets">'.$output.'</div><!/--all-wallets-->';
}


function template_preprocess_wallet_balance_bars(&$vars) {
  $data = $vars['data'];
  //if ($vars['data']['gross_in'] == 0 && $vars['data']['gross_out']) return;
  $vars['id'] = "given-gotten-".$vars['currency']->id().'-'.$vars['wallet']->id();
  $vars['functionname'] = str_replace('-', '_', $vars['id']);
  $vars['incoming'] = $data['gross_in'];
  $vars['outgoing'] = $data['gross_out'];
  $vars['show_in'] = $vars['currency']->format($data['gross_in']);
  $vars['show_out'] = $vars['currency']->format($data['gross_out']);
  $max = _mcapi_get_axis_max(max($data['gross_in'], $data['gross_out']));
  $vars['max'] = $max;
  $vars['vaxislabels'] = array(
    array('value' => 0, 'label' => $vars['currency']->format(0)),
    array('value' => $max/2, 'label' => $vars['currency']->format($max/2)),
    array('value' => $max, 'label' => $vars['currency']->format($max))
  );
  mcapi_add_gchart_js('corechart');
}

function mcapi_add_gchart_js($lib) {
  static $done = array();
  if (!count($done)) {
    drupal_add_js('http://www.google.com/jsapi');
  }
  if (!array_search($lib, $done)) {
    drupal_add_js("google.load('visualization', '1', {packages: ['corechart']});", 'inline');
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