<?php

/* $currcode is a machine name of a currency
 * $totals is an object with the following properties
 *   'balance' => float
 *   'gross_in' => float
 *   'gross_out' => float
 *   'volume' => float
 *   'count' => integer
 */

  $balance = theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->balance));
  $income = t('Income: !quant', array('!quant' => theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->gross_in))));
  $volume = t('Volume: !volume', array('!volume' => theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->volume))));
  $count = t('Transactions: @count', array('@count' => $totals->count));
?>

<div class = "transaction-totals <?php print $currcode;?>">
  <div class="balance"><?php print $balance; ?></div>
  <div class="gross-in"><?php print $income; ?></div>
  <div class="volume"><?php print $volume; ?></div>
  <div class="count"><?php print $count; ?></div>
</div>
