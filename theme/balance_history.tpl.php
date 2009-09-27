<?php 
 /*
  * Balance History Google Chart 
  * Takes data in the format below and outputs an <img> tag for a google chart.
  * Feel free to tweak the initial variables
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
  * 
  */


//this figure is a bit of guess work, based on 2048 chars - around 100 for the constant data divided by around 10 chars per point
define (MAX_CHART_POINTS, 140);
$dimensions = array(250, 200);
$legend = t('Balance over time');
print_r($points);
  $all_values = array(0);

  //TODO Add colours to the currency properties
  $randomcolors = array('ff8800','ff0088','0088ff','8800ff','0000ff','ffff00','ff00ff','ff0000', '00ff00', '0000ff');

foreach ($currencies as $currency){  //this loop draws one line for one currency
  $cid  = $currency->cid;
  $times = array();
  $values = array();
  //The system will choose here between three smoothing mechanisms, to make the best use fo the 2k URL limits of google charts.
  if (count($points)*2 < MAX_CHART_POINTS) {
    //unsmoothing mechanism, the true picture - adds intermediate points to produce perpendicular lines
    $sample_method = t('Steps');
    foreach ($points as $t => $bal){
      //make two points for each point, and calibrate
      $t1 = $t - $since_unixtime;
      //we could go further to reduce the number of chars and divide the time (unixtime) by something arbitrary
      $times[]=$t1;$times[]=$t1;
      $values[]=$bal;$values[]=$bal;
    }
    //put the arrays out of alignment by one point
    array_shift($times);
    array_pop($values);
  }
  //second smoothing mechanism, resample if there are too many points
  elseif (count($points) > MAX_CHART_POINTS) {
    $sample_method = t('Sampled');
    //we can sample the array by a factor of an integer only
    $sample_frequency = ceil(count($points) / MAX_CHART_POINTS);
    //make an array with every possible value - approx one for every pixel
    $previous_time = array_shift($point_times);
    $bal = array_shift($points);
    $all_points[$previous_time] = $bal;
    while ($time = array_shift($times)) {
      while ($previous_time < $time) {//adding the in between points
        $all_points[$previous_time] = $bal;
        $previous_time++;
      }
      $bal = array_shift($points);
      $all_points[$previous_time] = $bal;
    }
    unset($points);
    //$all_points now contains a value for every pixel time interval, ready for sampling
    //we reverse this array to be sure to sample the final value, which may be only a few seconds ago
    $reverse_points = array_reverse($all_points, TRUE);
    foreach ($reverse_points as $t=>$eachpoint){
      if (fmod($j, $sample_frequency) == 0){
        $values[$t-$since_unixtime] = $eachpoint;
      }
      $j++;
    }
    $times = array_keys($values);
  } else {
    //draws straight lines between the points
    $sample_method = t('Straight');
    foreach ($points as $time => $value) {
      $time -= $since_unixtime;
      $times[] = $time;
      $values [$time] = $value;
    }
  }
  
  //make the url encoded line from the x and y values
  $lines['mainline'.$cid] = implode(',',$times).'|'.implode(',',$values);
  $line_styles['mainline'.$cid] = 2;
  $line_colors['mainline'.$cid] = array_pop($randomcolors);
  //save the values to get the max and min later
  $all_values = array_merge($all_values, $values);
}
//determine the vertical limits according to a variable
if (variable_get('cc_history_chart_limits', 'trading') == 'limits') {
  $all_values['max'] = $account->limits[$cid]['max'];
  $all_values['min'] = $account->limits[$cid]['min'];
}
$max = max($all_values);
$min = min($all_values);

$ds=array();
while ($line = each($lines)) {
  $ds[] = implode(',',array(-1, time()-$since_unixtime ,$min,$max));
}

//now put the line into the google charts api format
$params['cht'] = 'lxy';
$params['chs'] = $dimensions;
$params['chd'] = 't:' . implode('|', $lines);
//optional parameters
if ($legend)$params['chtt'] = $legend;
$params['chds'] = implode(',',$ds);
$params['chxt'] = 'x,y';
$params['chxl'] = '0:|' . date('M y', $since_unixtime) . '|' . t('Now') . '|1:|' . $min . '|' . $max;
$params['chm'] = 'r,000000,0,1,-1'; //this is called a range marker, which we are using for the zero line
$params['chls'] = implode('|',$line_styles);
$params['chco'] = implode(',',$line_colors);
  
$src = 'http://chart.apis.google.com/chart?' . charts_build_query($params); 
$title = t("Balance over time for @user", array('@user' => theme('username', user_load($uid))));
if (strlen($src) > 2048) {
  watchdog('transactions', "Error creating balance chart: url to google charts exceeded 2048 charts.");
  drupal_set_message("Error creating balance chart: url to google charts exceeded 2048 charts.");
}
return '<img src="'.$src.'" id="chart" alt="' . $sample_method . ' ' . t('Balance history chart') . '" title="'.$title.'" class="gchart" />';
    
    
    
?>