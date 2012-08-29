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
<?php if ($min) print t('Min:') . ' '. theme('worth_field', array('currcode' => $currcode, 'quantity' => $min));; ?>
<br />
<?php if ($max) print t('Max:') . ' '. theme('worth_field', array('currcode' => $currcode, 'quantity' => $max));; ?>
<br />