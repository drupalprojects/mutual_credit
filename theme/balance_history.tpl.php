<?php 
 /*
  * Balance History Google Chart 
  * Takes data in the format below and outputs an <img> tag for a google chart.
  * Feel free to tweak the initial variables
  * //TODO This could be cached.
  * 
  * $points = array(
  *   '$cid' = array(
  *     '$unixtime' => $balance
  *   )
  * );
  * $currencies = array(
  *   '$cid' => Currency Obj()
  * );
  * $account = User Obj
  * $first_time = unitime integer of the moment the chart should start displaying
  *   This is subtracted from all the times to reduce the number of of chars per point in the google GET url
  * 
  */

//this figure is a bit of guess work, based on 2048 chars - around 100 for the constant data divided by around 10 chars per point
define (MAX_CHART_POINTS, 140);
define (GOOGLE_CHARTS_URI, 'http://chart.apis.google.com/chart');
$dimensions = array(250, 200);
$legend = t("Balance over time for @user", array('@user' => strip_tags(theme('username', $account))));
$all_values = array();
$values = array();

foreach ($currencies as $currency){  //this loop draws one line for one currency
  $cid  = $currency->cid;
  $times = array();
  $values = array();
  //determine the vertical limits according to the user's own account limits
  $all_values['max'] = $account->balances[$cid]['max'];
  $all_values['min'] += $account->balances[$cid]['min'];
  if (!count($points[$cid])) continue;
  //The system will choose here between three smoothing mechanisms, to make the best use fo the 2k URL limits of google charts.
  if (count($points[$cid])*2 < MAX_CHART_POINTS) {
    //unsmoothing mechanism, the true picture - adds intermediate points to produce perpendicular lines
    $sample_method = t('Steps');
    foreach ($points[$cid] as $t => $bal){
      //make two points for each point, and calibrate
      $t1 = $t - $first_time;
      //we could go further to reduce the number of chars and divide the time (unixtime) by something arbitrar
      $times[]=$t1;$times[]=$t1;
      $values[]=$bal;$values[]=$bal;
    }
    //put the arrays out of alignment by one point
    array_shift($times);
    array_pop($values);
  }
  //second smoothing mechanism, resample if there are too many points
  elseif (count($points[$cid]) > MAX_CHART_POINTS) {
    $sample_method = t('Sampled');
    //we can sample the array by a factor of an integer only
    $sample_frequency = ceil(count($points[$cid]) / MAX_CHART_POINTS);
    //make an array with every possible value - approx one for every pixel
    $previous_time = array_shift($point_times);
    $bal = array_shift($points[$cid]);
    $all_points[$previous_time] = $bal;
    while ($time = array_shift($times)) {
      while ($previous_time < $time) {//adding the in between points
        $all_points[$previous_time] = $bal;
        $previous_time++;
      }
      $bal = array_shift($points);
      $all_points[$previous_time] = $bal;
    }
    unset($points[$cid]);
    //$all_points now contains a value for every pixel time interval, ready for sampling
    //we reverse this array to be sure to sample the final value, which may be only a few seconds ago, so might otherwsie be sampled out
    $reverse_points = array_reverse($all_points, TRUE);
    foreach ($reverse_points as $t=>$eachpoint){
      if (fmod($j, $sample_frequency) == 0){
        $values[$t-$first_time] = $eachpoint;
      }
      $j++;
    }
    $times = array_keys($values);
  } else {
    //draws straight lines between the points
    $sample_method = t('Straight');
    foreach ($points[$cid] as $time => $value) {
      $values [$time - $first_time] = $value;
    }
    $times = array_keys($values);
  }
  
  //make the url encoded line from the x and y values
  $lines['mainline'.$cid] = implode(',',$times).'|'.implode(',',$values);
  $line_styles['mainline'.$cid] = 2;
  $line_colors['mainline'.$cid] = $currency->color;
  //save the values to get the max and min later
  $all_values = array_merge($all_values, $values);
}
$max = max($all_values);
$min = min($all_values);

$ds=array();
while ($line = @each($lines)) {
  $ds[] = implode(',',array(-1, time()-$first_time+1 ,$min,$max));
}

//now put the line into the google charts api format
$params['cht'] = 'lxy';
$params['chs'] = implode('x',$dimensions);
$params['chd'] = 't:' . @implode('|', $lines);
//optional parameters
if ($legend)$params['chtt'] = $legend;
$params['chds'] = implode(',',$ds);
$params['chxt'] = 'x,y';
$params['chxl'] = '0:|' . date('M y', $first_time) . '|' . t('Now') . '|1:|' . $min . '|' . $max;
$params['chm'] = 'r,888888,0,1,-1'; //this is called a range marker, which we are using for the zero line
$params['chls'] = @implode('|',$line_styles);
$params['chco'] = @implode(',',$line_colors);
  
//cleaner than http_build_query
foreach ($params as $key=>$val) {
    $args[] = $key . '=' . $val;
  }

$url =  GOOGLE_CHARTS_URI . '?' .implode('&', $args);
if (strlen($url) > 2048) {
  watchdog('transactions', "Error creating balance chart: url to google charts exceeded 2048 charts.");
  drupal_set_message("Error creating balance chart: url to google charts exceeded 2048 charts.");
}

//can't use theme_image because it expects a file
print '<img src="' . $url . '" alt="' . $legend . '" title="' . $legend . '" class="chart" />';
    