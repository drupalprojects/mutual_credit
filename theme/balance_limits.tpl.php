<?php
/*
 * Balance_limits.tpl.php
 * Themed display the user's balance limits for a given currency
 * Some variables can be set at the start
 * 
 * variables:
 * 
 * $account
 * $cid
 * $min
 * $max
 * $balance
 */ 
?>

<h5><?php print t('Balance limits'); ?></h5>
<p>Max: <?php print theme('money', $max); ?>
<br />Min: <?php print theme('money', $min); ?></p>