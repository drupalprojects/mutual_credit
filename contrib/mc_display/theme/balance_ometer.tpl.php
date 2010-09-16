<?php
// $Id$
/*
 * Balance_limits.tpl.php
 * Themed display the user's balance limits for a given currency
 * Some variables can be set at the start
 * 
 * variables:
 * 
 * $account
 * $min = array($cid => -100...);
 * $max = array($cid => 100...);
 * $balance = array($cid => 43...);
 * $currency = node object
 */

$colors = array($currency->data['color'], 'FFFFFF', $currency->data['color']);

$params = array(
  'cht' => 'gom',
  'chs' => '200x120',
  'chxt' => 'x,y',
  'chds' => $min .','. $max,
  //'chxr' => '1,'.$account->balances[$cid]['limit_min'] .','. $account->balances[$cid]['limit_max'] .'25',
  'chd' => 't:'.$balance,
  'chxl' => '1:|'. $min ."|". $max,
  'chco' => implode(',', $colors),
  'chl' => $balance,
  //'chtt' => $currency->title,
  'chxs' => '0,000000|1,000000',
  'chf' => 'bg,s,FFFFFFFF'
);

$url = GOOGLE_CHARTS_URI . http_build_query($params);
print '<img src="'.$url.'">';

