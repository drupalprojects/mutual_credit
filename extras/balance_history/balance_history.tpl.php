<?php
//$Id: balance_history.tpl.php,v 1.3 2010/12/08 11:43:18 matslats Exp $


 /*
  * Balance History Google Chart
  * Takes data in the format below and outputs an <img> tag for a google chart.
  * Feel free to tweak the initial variables
  * //TODO This could be cached.
  *
  * $account = User Obj
  * $histories = array(
  *   '$currcode' = array(
  *     '$unixtime' => $balance
  *     '$unixtime' => $balance
  *     etc...
  *   )
  * );
  */
$dimensions = array('x' => 250, 'y' => 200);
$lines = $line_styles = $maxes = $mins = array();
$maxes = array(10);
$mins = array(-10);
$colors = array('3072F3','FF0000');

//this loop draws one line for each currency
foreach ($histories as $currcode => $history){
  //make the url encoded line from the x and y values
  $lines[$currcode] = implode(',',array_keys($history)) .'|'. implode(',', $history);
  $line_styles[$currcode] = 2;
  $maxes[] = max($history);
  $mins[] = min($history);
  $chcos[] = next($colors);
  $chdls[] = currency_load($currcode)->human_name;
}

//$lines['zero'] = $account->created .','.REQUEST_TIME .'|0,0';
//$line_styles['zero'] = 3;
//  $curr_names[] = '';

  $max = max($maxes);
  $min = min($mins);
  $min_label = strip_tags(theme('worth_item', array('quantity' => $min, 'currcode' => $currcode)));
  $mid_label = strip_tags(theme('worth_item', array('quantity' => ($min + $max)/2, 'currcode' => $currcode)));
  $max_label = strip_tags(theme('worth_item', array('quantity' => $max, 'currcode' => $currcode)));

//now put the line into the google charts api format
$params = array(
  'cht' => 'lxy',
  'chs' => implode('x', $dimensions),
  //optional parameters
  'chxt' => 'x,y', //needed for axis labels
  'chls' => implode('|', $line_styles),
  'chco' => implode(',', $chcos), //colors
  'chdl' => implode('|', $chdls),
  'chdlp' => 'b'//put the key on the baseline
);

$params['chd'] = 't:' . implode('|', $lines);
if ($min != 0) { //0.5 means half way up, 0.6 must be larger than 0.5, thickness, priority
  $zeropoint = 1-abs($min)/(abs($min)+$max);
  //height of the line must be 1 pixel
  $params['chm'] = implode(',', array('r','dddddd',0,$zeropoint, $zeropoint+0.001));
}
$last_time = array_pop(array_keys($history)) + 1;
$params['chds'] = implode(',',array(-1, $last_time, $min, $max));
$params['chxl'] = '0:|' . date('M y', $account->created) . '|' . t('Now') . '|1:|' . implode('|', array($min_label, $mid_label, $max_label));

//cleaner than http_build_query
foreach ($params as $key=>$val) {
  $args[] = $key . '=' . $val;
}
?>
<img src = "http://chart.apis.google.com/chart?<?php print substr(implode('&', $args), 0, 2010); ?>" class = "chart" />