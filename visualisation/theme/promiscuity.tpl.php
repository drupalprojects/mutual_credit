<?php
//Id;
/*
 * Promiscuity
 * Show how many partners a user has had
 * (since a certain date)
 * Variables:
 * $partners
 * Will be integer for an individual and float for an average
 */
?>
<div class = "promiscuity">
  <?php print gettype($partners) == 'integer' ?
    t('Partners:') :
    t('Average promiscuity'); ?><br />
  <font size = "6"><?php print $partners; ?></font>
</div>
