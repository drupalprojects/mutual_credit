<?php
/*
 * $data => an array where every key is a unixperiod
 * $limit => the number if rows to display
 * $period => the number of seconds by which the array key was divided
 */

  switch ($period) {
    case  'yW': $colhead = t('Week Num'); break;
    case  'yz': $colhead = t('Day Num'); break;
    case  'ym': $colhead = t('Month'); break;
  }


$headings = array($colhead, t('Quantity'));
$rows = array();
foreach ($data as $key => $val){
  $rows[] = array($key, $val);
}

print theme('table', $headings, $rows);