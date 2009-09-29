<?php
/*
 * Balance_limits.tpl.php
 * Themed display of the balance in comparison to the user's balance limits for a given currency
 * Some variables can be set at the start
 * 
 * variables:
 * 
 * $account
 * $cid
 * $min
 * $max
 * $balance
 */ 
define (GOOGLE_CHARTS_URI, 'http://chart.apis.google.com/chart');
$dimensions = array(150,100);//pixels
$legend = '';
$colors = array('0000ff', 'ffffff', '00ff00');
$labels = array('min', 'max');

$params=array();
$params['cht'] = 'gom';
$params['chs'] = implode('x',$dimensions);
$params['chd'] = 't:' . $balance;
$params['chds'] = $min. ',' . $max;
if ($colors)$params['chco'] = implode(',',$colors);
$params['chf'] =  'bg,s,FFFFFF00';
$params['chtt'] = $labels[0] . '  ->  ' . $labels[1];

//cleaner than http_build_query
foreach ($params as $key=>$val) {
    $args[] = $key . '=' . $val;
  }
$params =  implode('&', $args);

print '<img src="'.GOOGLE_CHARTS_URI . '?' . $params.'" alt="' . $legend . '" title="'.$legend.'" class="chart" />';