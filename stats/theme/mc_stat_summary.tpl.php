<?php
/*
 * $top_exchanges = array(uid => num of trades)
 * $weekly_exchanges = array (weekofyear => num of trades)
 * $active_users = integer
 * $user_count = integer
 *
 */
?>
<div style ="float:left;border:thin solid grey; margin:1em; padding:6pt; text-align:center;">
<h4><?php print t('Active users'); ?></h4>
<p><font size = "+2"><?php print $active_users; ?></font> <?php print t('active'); ?>
<font size = "+2">/<?php print $user_count; ?></font> <?php print t('total'); ?></p>
<h4><?php print t('Trades / week'); ?></h4>
<p><font size = "+2"><?php print round(array_sum($weekly_exchanges) / count($weekly_exchanges), 1); ?></font></p>
</div>