<?php
/*
 * preprocessor should do the theming here
 * balance_limits.tpl.php
 * $currcode
 * $max
 * $min
 * $uid
 * $balance
 */

print t('Balance:') .' '. $balance; ?>
<br />
<?php if ($min) print t('Min:') . ' '. theme('worth_item', array('currcode' => $currcode, 'quantity' => $min));; ?>
<br />
<?php if ($max) print t('Max:') . ' '. theme('worth_item', array('currcode' => $currcode, 'quantity' => $max));; ?>
<br />
