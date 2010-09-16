<?php 
// $Id$
/* themes the balances for a given user.
 * Available variables
 * $balances is an array of the form:
 * Array (
    [$cid] => Array (
      [cleared_balance] => float or int
      [pending_dif] => float or int
      [gross_in] => float or int
      [gross_out] => float or int
      [max_in] => float or int
      [max_out] => float or int
      [rating] => float or int
      [max] => float or int
      [min] => float or int
    )
  )
 * also $currencies = array()
 * plus the usual
 */
if (!count($balances)) {
  print t("Yet to trade.");
  return;
}

$rows = array(
  0 => array(0 => t('Cleared balance')),
  1 => array(0 => t('Total income')),
  2 => array(0 => t('Spending limit'))
);
foreach ($currencies as $currency) {
  if ($currency->data['ratings']) $rows[3] = array(0 => t('Ratings'));
}

$headings = array(0 => '');
foreach ($balances as $cid => $set) {
  $headings[] = $currencies[$cid]->title;
  foreach($set as $value) {
    $rows[0][$cid] = theme('money', $set['cleared_balance'], $cid);
    $rows[1][$cid] = theme('money', $set['gross_in'], $cid);
    $rows[2][$cid] = theme('money', $set['max_out'], $cid);
    if ($currencies[$cid]->data['ratings']) {
      $rows[3][$cid] = $set['rating'];
    }
    else {
       $rows[3][$cid] = '';
    }
  }
}

print theme('table', $headings, $rows);
