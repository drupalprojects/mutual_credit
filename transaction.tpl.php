<?php
// $Id: node-exchange.tpl.php,v 1.1.2.3 2010/12/06 13:19:46 hutch Exp $
/*
 * NOTE:
 *
 * Each currency can have its own transaction template
 * Simply rename this file and rename it thus in your theme directory:
 * transaction__X.tpl.php where X is the currency id
 *
 * see template_preprocess_transaction() for details
 *
 * $transaction     //entity object
 * $view_mode       // either 'sentence' or 'certificate'
 * $type            //
 * $state           // 1 = pending, 0 = completed, -1 = erased
 * $recorded        // date formatted using drupal's 'medium' date format
 * $payer           // name linked to payer profile
 * $payee           // name linked to payee profile
 * $worth          // an comma separated list of formatted transaction values (in different currencies)
 *
 * need to do some more work on the icon size
 * for now, you might want to include your own large-size graphic instead
 * using $transaction->quantity
 * tip: $currency = currency_load($transaction->currcode);
 *
 * This template doesn't use normal template syntax because it's based on a sentence structure and needs to retain a coherent translatable string
 */
$replacements = array(
  '@recorded' => $recorded,
  '!payer' => $payer,
  '!payee' => $payee,
  '!worth' => $worth,
);

if ($view_mode == 'sentence') {
  switch ($transaction->state) {
    case TRANSACTION_STATE_FINISHED:
      print t("On @recorded, !payer gave !payee !worth", $replacements);
      break;
    case TRANSACTION_STATE_ERASED:
      print t("On @recorded, !payer did not give !payee !worth. (DELETED)'", $replacements);
      break;
    case TRANSACTION_STATE_PENDING:
      print t("!payer should give !payee !worth.'", $replacements);
      break;
  }
  return;
}
else {
  $date = t('On @date', array('@date' => $recorded));
  $movement = $state == TRANSACTION_STATE_FINISHED ?
    t('!payer <strong>paid</strong> !payee', $replacements) :
    t('!payer <strong>will pay</strong> !payee', $replacements);
  $sum = t('the sum of !worth', array('!worth' => '<p style="font-size:250%;">'. $worth .'</p>'));
?>

<div class="exchange">
  <p><?php print $date; ?></p>
   <p><?php print $movement; ?></p>
   <p><?php print $sum; ?> </p>
</div>

<?php } ?>
