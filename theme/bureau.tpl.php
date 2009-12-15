<?
/* 
 * This themes several items of information concerning a user's financial activity.
 * Variables provided by preprocess functions
 * $account = Obj
 * $balances = themed grid
 * $pending
 * $history
*/
?>

<h3>Balances</h3>
<?php print $balances; ?>


<h3>History</h3>
<?php 
$options = array();//see template_preprocess_balance_history() for $options and defaults
print theme('balance_history', $account, $options); 
?>

<h3>Credit</h3>
<?php 
foreach(currencies_load(array('uid'=>$account->uid)) as $currency) {
  print theme('balance_limits', $account, $currency->cid); 
}
?>

<h3>Trading volumes</h3>
<?php 
foreach(currencies_load(array('uid'=>$account->uid)) as $currency) {
  print theme('period_volumes', $account, $currency->cid); 
}
?>

<h3>Pending Transactions</h3>
<?php print $pending; ?>

<h3>Recent Transactions</h3>
<?php print $history; ?>