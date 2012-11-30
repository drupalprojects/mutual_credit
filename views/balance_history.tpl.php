<?php
//$Id: balance_history.tpl.php,v 1.3 2010/12/08 11:43:18 matslats Exp $

//this is calcluated as 2048 max chars in a google charts url - 250 characters for all the other data divided by 13 characters per chart-point
define ('MAX_CHART_POINTS', 140);

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
  if (empty($history)) continue;
  $currency = currency_load($currcode);
  $times = array();
  $values = array();
  $first_time = reset($history);
  //The system will choose here between three smoothing mechanisms, to make the best use fo the 2k URL limits of google charts.
  if (count($history)*2 < MAX_CHART_POINTS) {
    //unsmoothing mechanism, the true picture - adds intermediate points to produce perpendicular lines
    //make two points for each point, then slip the x against the y to make the step effect
    while (list($t, $bal) = each($history)){
      //subtract the first time to reduce the number of chars - should save about 3 per point
      $t1 = intval(($t - $first_time) / $dimensions['x']);
      //we could go further to reduce the number of chars and divide the time (unixtime) by something arbitrary,
      //like the number of pixels in the x axis
      $times[]=$t1;
      $times[]=$t1;
      $values[]=$bal;
      $values[]=$bal;
    }
    //remove the first time and the last value to make the stepped effect!
    array_shift($times);
    array_pop($values);
  }
  //second smoothing mechanism, resample if there are too many points
  elseif (count($history) > MAX_CHART_POINTS) {
    $history_times = array_keys($history);
    $history_values = array_values($history);
    $first_time = reset($history_times);
    $last_time = end($history_times);
    $history_values[] = end($history);//repeat the last time because it has to have the same number of elements as the temp array, with the sample moment added
    $sampling_interval = ceil(($last_time - $first_time) / MAX_CHART_POINTS);
    for ($i = 0; $i <= MAX_CHART_POINTS; $i++) {
      $sample_time = $first_time + $i*$sampling_interval; // arbitrary granularity figure
      $temp = $history_times;
      $temp[] = $sample_time;
      sort($temp);
      $sample_position = array_search($sample_time, $temp);
      $times[] = floor(($sample_time-$first_time)/10000);
      $values[] = $history_values[$sample_position];
    }
  }
  else { //using given data
    //draws straight diagonal lines between the points
    foreach ($history as $time => $value) {
      $values [$time - $first_time] = $value;
    }
    $times = array_keys($values);
  }
  //make the url encoded line from the x and y values
  $lines[$currcode] = implode(',',$times) .'|'. implode(',', $values);
  $line_styles[$currcode] = 2;

  $maxes[] = max($values);
  $mins[] = min($values);
  $chcos[] = next($colors);
  $chdls[] = $currency->human_name;
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
$params['chds'] = implode(',',array(-1, array_pop($times) +1, $min, $max));
$params['chxl'] = '0:|' . date('M y', $account->created) . '|' . t('Now') . '|1:|' . implode('|', array($min_label, $mid_label, $max_label));

//cleaner than http_build_query
foreach ($params as $key=>$val) {
  $args[] = $key . '=' . $val;
}
?>
<img src = "http://chart.apis.google.com/chart?<?php print substr(implode('&', $args), 0, 2010); ?>" class = "chart" />