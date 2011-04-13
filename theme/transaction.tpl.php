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
 * $view_mode       // either 'summary' or 'certificate'
 * $description     //
 * $type            //
 * $state           // 1 = pending, 0 = completed, -1 = erased
 * $recorded        // date formatted using drupal's 'medium' date format
 * $payer           // name linked to payer profile
 * $payee           // name linked to payee profile
 * $amount          // formatted quantity.
 *
 * need to do some more work on the icon size
 * for now, you might want to include your own large-size graphic instead
 * using $transaction->quantity
 * tip: $currency = currency_load($transaction->cid);
 *
 *
 */

if ($view_mode == 'summary') {
  $replacements = array(
    '@recorded' => $recorded,
    '!payer' => $payer,
    '!payee' => $payee,
    '!amount' => $amount,
    '@description' => $description,
  );
  switch ($transaction->state) {
    case TRANSACTION_STATE_PENDING:
      print t("On @recorded, !payer will pay !payee !amount for '@description'", $replacements);
      break;
    case TRANSACTION_STATE_FINISHED:
      print t("On @recorded, !payer gave !payee !amount for '@description'", $replacements);
      break;
    case TRANSACTION_STATE_ERASED:
      print t("On @recorded, !payer did not give !payee !amount for '@description'. (DELETED)'", $replacements);
      break;
  }
  return;
}
else {
  $date = t('On @date', array('@date' => $recorded));
  $movement = $state == TRANSACTION_STATE_PENDING ?
    t('!payer <strong>will pay</strong> !payee', array('!payer' => $payer, '!payee' => $payee)) :
    t('!payer <strong>paid</strong> !payee', array('!payer' => $payer, '!payee' => $payee));
  $sum = t('the sum of !amount', array('!amount' => '</p><p style="font-size:250%">'.$amount));
  $reason = t('for !reason', array('!reason' => '<strong>'.$description.'</strong>'));


?>

<div class="exchange">
  <p><?php print $date; ?></p>
   <p><?php print $movement; ?></p>
   <p><?php print $sum; ?> </p>
  <p><?php print $reason; ?></p>
</div>

<?php } ?>