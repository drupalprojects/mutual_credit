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
 * $worth          // a comma separated list of formatted transaction values (in different currencies)
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
?>
<div class = "<?php print $classes; ?>">
  <?php if ($view_mode == 'sentence') {
    print t("On @recorded, !payer gave !payee !worth", $replacements);
  }
else {
  print render($pending_signatures); //floating the right, by default
  $date = t('On @date', array('@date' => $recorded));
  $movement = t('!payer <strong>paid</strong> !payee', $replacements);
  $sum = t('the sum of !worth', array('!worth' => '<div style="font-size:250%;">'. $worth .'</div>'));
  ?>
    <p><?php print $date; ?><br /><br />
    <?php print $movement; ?><br /><br />
    <?php print $sum; ?></p>
    <?php print render($additional);
    
    if ($children) { ?>
    <div id="dependent-transactions" style ="border:medium solid grey; text-align:center;">
      <h3><?php print t('Dependent transactions'); ?></h3>
      <?php print render ($children); ?>
    </div>
    <?php }
  } ?>

</div><!-- end transaction-->
