<?php
/*
 * balance_limits.tpl.php
 * $currency
 * $spend_limit
 * $earn_limit
 */
?>

<?php if ($spend_limit) print t('Spending limit:') . ' '. $spend_limit; ?>
<br />
<?php if ($earn_limit) print t('Receiving limit:') . ' '. $earn_limit; ?>

