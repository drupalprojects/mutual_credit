<?php
/*
 * balance_limits.tpl.php
 * $currency
 * $max
 * $min
 * $balance
 * $adjust
 */

if ($adjust) {
print t('Expenditure limit:') . ' '. abs($min + $balance); ?>
<br />
<?php print t('Income limit:') . ' '. $max = $balance;
}

else {

print t('Balance:') .' '. $balance; ?>
<br />
<?php print t('Min:') . ' '. $min; ?>
<br />
<?php print t('Max:') . ' '. $max; ?>
<br /><?php
}
