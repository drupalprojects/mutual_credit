<?
/*
 * $top_traders = array(uid => num of trades)
 * weekly_exchanges = array (weekofyear => num of trades)
 * $active_users = integer
 * $user_count = integer
 *
 * Actually $user count should be the same as Active users
 */
?>
<font size = "+2"><?php print $active_users; ?></font> <?php print t('Active users'); ?>
<h4><? print t('Trades per week'); ?></h4>
<?php if (count($weekly_exchanges)) { ?>
  <font size = "+2"><?php print round(array_sum($weekly_exchanges) / count($weekly_exchanges), 1); ?></font>
  <?php } ?>
<h4><? print t('Top traders'); ?></h4>
<?php print theme('top_users', $top_exchangers);