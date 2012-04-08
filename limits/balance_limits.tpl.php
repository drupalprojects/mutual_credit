<?php
/*
 * balance_limits.tpl.php
 * $currency
 * $max
 * $min
 * $balance
 */

print t('Balance:') .' '. $balance; ?>
<br />
<?php if ($min) print t('Min:') . ' '. $min; ?>
<br />
<?php if ($max) print t('Max:') . ' '. $max; ?>
<br />