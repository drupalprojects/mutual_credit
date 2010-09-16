<?php 
// $Id$
/*
 * recent_volumes.tpl.php
 * 
 * For each statistical period, shows the user's income and expenditure in a given currency
 * $volumes contains every currency for every period, but this visualises only the first currency for all periods
 * Single currency only and first period only
 * 
 * $variables:
 * $volumes = array(
 *   '$period' => array( //0 $period means since ever
 *     '$cid' = array (
 *       'income' => NUM
 *       'expenditure' => NUM
 *     ),
 *   )
 * );
 * 
 * $account
 * $cid
 */ 

define (GOOGLE_CHARTS_URI, 'http://chart.apis.google.com/chart');
$dimensions = array(200,200);//pixels
$legend = '';
$colors =  array('4D89D9','C6D9FD');
$labels = array('min', 'max');
$max_ever = max($volumes[0][$cid]['income'], $volumes[0][$cid]['expenditure']) or $max_ever=0;
$last_period = array_pop(array_keys($volumes));

$max_recent = max($volumes[$last_period][$cid]);

$top_value = 10*ceil($max_ever/10);//this is how high the y axis goes

$params=array();
$params['cht']= 'bvg';
$params['chs']= implode('x',$dimensions); //chs=<width in pixels>x<height in pixels>
$params['chds'] = '0,' . $top_value;
$params['chbh'] = 'a,2,10'; //chbh=<bar width>,<space between bars>,<space between groups>
$params['chco'] = implode(',',$colors);
$params['chf'] =  'bg,s,EFEFEF00';
//floatval is to convert NULL to zero without compromising float values
$params['chd'] = 't:' . floatval($volumes[0][$cid]['income']) . ',' . floatval($volumes[$last_period][$cid]['income'])  . '|' . floatval($volumes[0][$cid]['expenditure']) . ',' . floatval($volumes[$last_period][$cid]['expenditure']);
//axis labels
$params['chxt'] = 'x,y,x';
$params['chxl'] = '0:|in,out|in,out||1:|0|'. $top_value/2 . '|'. $top_value . '|2:|ever|recent';
if ($legend)$params['chtt'] = 'chtt=' . $legend;


//cleaner than http_build_query
foreach ($params as $key=>$val) {
    $args[] = $key . '=' . $val;
  }
$params =  implode('&', $args);

print '<img src="'.GOOGLE_CHARTS_URI . '?' . $params.'" alt="' . $legend . '" title="'.$legend.'" class="chart" />';

/*
http://chart.apis.google.com/chart?cht=bvg&chs=200x200&chds=0,50,0,50&chbh=a,2,10&chco=4D89D9,C6D9FD&chf=bg,s,EFEFEF00&chd=t:1,1|50&chxt=x,y,x&chxl=0:|in,out|in,out||1:|0|25|50|2:|ever|recent
*/