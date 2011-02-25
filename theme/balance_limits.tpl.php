<?php
/*
 * Balance_limits.tpl.php
 * Themed display the user's balance limits for a given currency
 * This template should render as many currencies as there are
 * 
 * variables:
 * 
 * $account
 * $min = array($cid => -100...);
 * $max = array($cid => 100...);
 * $balance = array($cid => 43...);
 * $currencies = array($cid => Object...)
 */ 
?>

<?php foreach(array_keys($min) as $cid) { ?>
<p>Max: <?php print theme('money', $max[$cid]); ?>
<br />Min: <?php print theme('money', $min[$cid]); ?></p>
<?php } ?>
