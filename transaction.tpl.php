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
 * $view_mode       // either 'sentences' or 'certificate'
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
 * This template doesn't use normal template syntax because it's based on a sentence structure and needs to retain a coherent translatable string
 * It is rather complex because of the need for translation
 */
$replacements = array(
  '@recorded' => $recorded,
  '!payer' => $payer,
  '!payee' => $payee,
  '!worth' => $worth,
);
if ($view_mode == 'certificate') {
  print render($pending_signatures); //floating the right, by default
  $replacements['!worth'] = '<span class = "quantity">'. $replacements['!worth'] .'</div>';
  $certificate_string = t(
    'On @recorded<br />!payer <strong>paid</strong> !payee</br />the sum of !worth',
    $replacements
  );
  $certificate_string = str_replace('<br/>', '<br /><br />', $certificate_string);
}
?>
<div class = "<?php print $classes; ?>">
  <?php if ($view_mode == 'certificate') : ?>
    <?php print $certificate_string; ?>
    <?php print render($additional); ?>
  <?php elseif ($view_mode == 'sentences') :
    if ($state > 0) {//transaction states > 0 are 'counted'
      print t("On @recorded, !payer gave !payee !worth", $replacements);
    }
    else {//this is most likely a 'pending' transaction which is state -1
      print t("On @recorded, !payer will give !payee !worth", $replacements);
    }
  endif; ?>

  <?php if (isset($dependents)) : // all the remaining transactions are already rendered as sentences ?>
  <div id="dependent-transactions">
    <h3><?php print t('Dependent transactions'); ?></h3>
    <?php print render ($dependents); ?>
  </div>
  <?php endif; ?>

</div><!-- end transaction-->

