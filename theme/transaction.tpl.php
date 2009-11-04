<?php 
/* Variables available...

  $nid => 44
  $uid => 3
  $status => 1
  $created => 1251757109
  $changed => 1251757109
  $comment => 0
  $promote => 0
  $moderate => 0
  $sticky => 0
  $tnid => 0
  $translate => 0
  $vid => 44
  $revision_uid => 1
  $title => gift from carl to darren
  $name => carl
  $payer_uid => 3
  $payee_uid => 4
  $starter_uid => 3
  $completer_uid => 4
  $cid => 0
  $quantity => 1:15 (quarters) or 1.25 (decimal)
  $transaction_type => outgoing_direct
  $quality => 0
  $state => 0
  $submitted => Mon, 08/31/2009 - 22:18
  $description => gift from carl to darren
  $starter => <a href="/user/3" title="View user profile.">carl</a>
  $completer => <a href="/user/4" title="View user profile.">darren</a>
  $amount => theme(money $quantity...)
  $balance =>  theme(money $quantity...)
  $payee => <a href="/user/3" title="View user profile.">carl</a>
  $payer => <a href="/user/4" title="View user profile.">darren</a>
  $transaction_link => <a href="/node/44" class="active">gift from carl to darren</a>
  $actions = some buttons in html
)
*/ 
$replacements = array(
  '!starter' => $starter, 
  '!completer'=> $completer, 
  '!amount' => $amount, 
  '!transaction_link' => $transaction_link, 
  '!payer' => $payer, 
  '!payee' => $payee, 
  '@submitted' => $submitted
);
?>

<div class="transactions<?php if ($state == TRANSACTION_STATE_PENDING) print '-pending'; ?>">
<?php 
if ($teaser) { // this is a one liner
  switch($state) {
    case TRANSACTION_STATE_PENDING:
      if (substr($transaction_type, 0, 8) == 'outgoing') {
        print t("!starter will give !completer !amount for '!transaction_link'", $replacements);
      } else {
        print t("!starter will receive !amount from !completer for '!transaction_link'", $replacements);
      }
      break;
    case TRANSACTION_STATE_COMPLETED: 
      print t("On @submitted, !payer gave !payee !amount for '!transaction_link'", $replacements);
      break;
    case TRANSACTION_STATE_DELETED: 
      print t("On @submitted, !payer did not give !payee !amount for '!transaction_link'. (DELETED)'", $replacements);
  }

} else { ?>
  <h3>Transaction Certificate <?php if ($state == TRANSACTION_STATE_PENDING) print ' - PENDING'; ?></h3>
  <hr/>
  <p><?php print $submitted; ?></p>
  <p><?php print $payer; ?>
   <?php if ($state == TRANSACTION_STATE_PENDING) print ' will pay '; else print ' paid '; ?> 
   <?php print $payee; ?><br />
   the sum of <span style="font-size:250%"> <?php print $quantity . '<span style="line-height:115%"> ' . $currency->title; ?></span></p>
  <p>"<strong><?php print $description; ?></strong>"
   <?php if ($state == TRANSACTION_STATE_PENDING) print 'to be exchanged '; else print 'was exchanged '; ?><?php
    if ($rating) {
      print ", and $payer rated the transaction as '$rating'";
    }
}?>
<?php print $actions; ?>
</div>