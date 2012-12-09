<?php
/*
 * theme implementation of pending_signatures
 * show the $transaction->pending signatures, signed and unsigned, with links
 *
 * variables are
 * $pending signatures - original data pulled from transaction object
 * $signatories - intermediate data saying how to theme it, with links and access control done
 * $signoff_link - is a link as a render array, checked for access control
 * $rows produced by template_preprocess_pending_signatures, with themed cells and links as render arrays
 * $finished = boolean whether or not the transaction->state is TRANSACTION_STATE_FINISHED
 */
$title = $finished ? t('Signed by') : t('Awaiting Signatures');
?>
<div style ="float:right" id ="pending-signatures">
  <h2><?php print $title; ?></h2>
  <?php if ($signoff_link) print '('.render($signoff_link) .')'; ?>
  <?php print render($table); ?>
</div>