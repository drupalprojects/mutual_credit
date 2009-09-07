<?php 
/* Variables available...
    $transaction = Object used internally
    $title => string
    $submitted => date
    $amount => themed amount e.g. $10
    $starter => link to user
    $completer => link to user
    $payee => link to user
    $payer => link to user
    $rating => probably integer
    $state => either TRANSACTION_STATE_COMPLETED or TRANSACTION_STATE_PENDING
    $zebra => odd or even
    $is_admin TRUE or FALSE
    $logged_in => 1
    $transaction_url = URL
    $rich_text => TRUE or FALSE
)
*/ 
if ($rich_text) $amount = $transaction->quantity . ' ' . $transaction->currency->title;
$replacements = array('!starter' => $starter, '!completer'=> $completer, '!amount'=>$amount, '!transaction_url'=>$transaction_url, '!payer'=>$payer, '!payee'=>$payee, '@submitted'=>$submitted);
?>

<div id="transactions<?php if ($state == TRANSACTION_STATE_PENDING) print '-pending'; ?>">
<?php 
if ($teaser) { // this is a one liner
  switch($state) {
    case TRANSACTION_STATE_PENDING:
      if (substr($transaction->transaction_type, 0, 8) == 'outgoing') {
        print t("!starter will give !completer !amount for '!transaction_url'", $replacements);
      } else {
        print "!starter will receive !amount from !completer for '!transaction_url'";
      }
      break;
    case TRANSACTION_STATE_COMPLETED: 
      print t("On @submitted, !payer gave !payee !amount for '!transaction_url'", $replacements);
      break;
    case TRANSACTION_STATE_DELETED: 
      print t("On @submitted, !payer did not give !payee !amount for '!transaction_url'. (DELETED)'", $replacements);
  }

} else { ?>
  <h3>Transaction Certificate <?php if ($state == TRANSACTION_STATE_PENDING) print ' - PENDING'; ?></h3>
  <hr/>
  <p><?php print $submitted; ?></p>
  <p><?php print $payer; ?>
   <?php if ($state == TRANSACTION_STATE_PENDING) print ' will pay '; else print ' paid '; ?> 
   <?php print $payee; ?><br />
   the sum of <span style="font-size:250%"> <?php print $transaction->quantity . '<span style="line-height:115%"> ' . $currency; ?></span></p>
  <p>"<strong><?php print $description; ?></strong>"
   <?php if ($state == TRANSACTION_STATE_PENDING) print 'to be exchanged '; else print 'was exchanged '; ?><?php
    if ($rating) {
      print ", and $payer rated the transaction as '$rating'";
    }
}?>
<p><?php print $actions; ?></p>
</div>