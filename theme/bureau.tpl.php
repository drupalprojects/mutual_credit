<?
/* 
 * This themes several items of information concerning a user's financial activity.
 * 
 * the chart callback 'options' can be edited from here, e.g.
 * print_r($chart_callbacks);
 * $chart_callbacks['user_balance_history'][1]['legend'] = 'historical balances';
 * $chart_callbacks['chart_user_gross_recent_volumes'][1]['dimensions'] = '200x200';
 * $chart_callbacks['chart_user_gross_recent_volumes'][1]['period'] = '1 year';
 * for more info see the chart functions
*/
?>

<h3>Balances</h3>
<?php print $balances; ?>


<h3>History</h3>
<?php 
$options = array();//see template_preprocess_balance_history() for $options and defaults
print theme('balance_history', $account, array()); 
?>

<h3>Credit</h3>
<?php 
print theme('balance_limits', $account); 
?>

<h3>Trading volumes</h3>
<?php 
print theme('period_volumes', $account); 
?>

<h3>Pending Transactions</h3>
<?php print $pending; ?>

<h3>Recent Transactions</h3>
<?php print $history; ?>