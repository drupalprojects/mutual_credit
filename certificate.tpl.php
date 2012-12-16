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
 * $type            //
 * $state           // 1 = pending, 0 = completed, -1 = erased
 * $recorded        // date formatted using drupal's 'medium' date format
 * $payer           // name linked to payer profile
 * $payee           // name linked to payee profile
 * $worth          // a comma separated list of formatted transaction values (in different currencies)
 * $children        //an array of other transactions with the same serial number
 *
 * need to do some more work on the icon size
 * for now, you might want to include your own large-size graphic instead
 * using $transaction->quantity
 * tip: $currency = currency_load($transaction->currcode);
 *
 * If anyone can think of a more elegant way to make this translatable...
 */
$replacements = array(
  '@recorded' => $recorded,
  '!payer' => $payer,
  '!payee' => $payee,
  '!worth' => $worth,
);
  $replacements['!worth'] = '<span class = "quantity">'. $replacements['!worth'] .'</span>';
  $certificate_string = t(
    'On @recorded<br />!payer <strong>paid</strong> !payee</br />the sum of !worth',
    $replacements
  );
  $certificate_string = str_replace('<br />', '<br /><br />', $certificate_string);

?>
<!--transaction.tpl.php-->
<div class = "<?php print $classes; ?>">
  <?php print render($pending); //floating on the right, by default ?>
  <?php print $certificate_string; ?>

  <?php if (isset($dependents)) : // all the remaining transactions are already rendered as tokenised strings ?>
  <div id="dependent-transactions">
    <h3><?php print t('Dependent transactions'); ?></h3>
    <?php print render ($dependents); ?>
  </div>
  <?php endif; ?>

  <?php print render($additional); //any fields we don't know about'?>

</div><!-- end transaction-->

