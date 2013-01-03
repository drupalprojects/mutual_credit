<?php
/*
 * theme implementation of pending_signatures
 * show the $transaction->pending signatures, signed and unsigned, with links
 *
 * variables are
 * $transaction - original data pulled from transaction object
 *
 * css is calculated in hook_theme and included with
 *
 */
//inject a bit of css to change the background picture of the transaction certificate
$background =  "background-repeat: no-repeat; background-position: center;";

foreach ($transaction->pending_signatures as $uid => $status) {
  if ($status == 1)  {
    $row = array(
      'title' => t('Awaiting signature'),
      'class' => 'pending',
      'style' => "background-image:url(\"".url('misc')."/message-24-warning.png\"); width:20px; $background"
    );
  }
  else {
    $row = array(
      'title' => t('Signed'),
      'class' => 'signed',
      'style' => "background-image:url(\"".url('misc')."/message-24-ok.png\"); width:20px; $background"
    );
  }

  $rows[$uid] = array(
    format_username(user_load($uid)),
    $row
  );
}
$sign_link = _get_signoff_link($transaction);
if (!$sign_link) $sign_link = _get_sign_link($transaction);
$table = array(
  '#theme' => 'table',
  '#attributes' => array('style' => "width:15em;"),
  '#rows' => $rows
);
?>
<div style ="float:right" id ="pending-signatures">
  <h2><?php print $transaction->state == TRANSACTION_STATE_FINISHED ? t('Signed by') : t('Awaiting Signatures'); ?></h2>
  <?php if ($sign_link) print render($sign_link); ?>
  <?php print render($table); ?>
</div>