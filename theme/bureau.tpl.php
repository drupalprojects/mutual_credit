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
foreach ($chart_callbacks as $chart_name => $args) {
  $charts[$chart_name] = call_user_func_array($chart_name, $args);
}

?>
<h3>Balances</h3>
<?php print $balances; ?>

<?php if ($charts['chart_user_balance_history']) {?>
<h3>History</h3>
<?php print $charts['chart_user_balance_history']; ?>
<?php } ?>

<?php if ($charts['chart_balance_limits']) {?>
<h3>Credit</h3>
<?php print $charts['chart_balance_limits']; ?>
<?php } ?>

<?php if ($charts['chart_user_gross_recent_volumes']) {?>
<h3>Trading volumes</h3>
<?php print $charts['chart_user_gross_recent_volumes']; ?>
<?php } ?>

<h3>Pending Transactions</h3>
<?php print $pending; ?>

<h3>Recent Transactions</h3>
<?php print $history; ?>