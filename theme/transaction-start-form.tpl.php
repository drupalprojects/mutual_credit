<?php
/*
 * transaction_start_form.tpl.php 
 * An opportunity to rearrange the transaction form without using hook_form_alter.
 * Only the non-hidden fields below are avaialble as theming variables
 * If expected variables not present, check your form generation parameters and then template_preprocess_transaction_start_form()
 * 
 * Variables available
 * $user = Logged in user Object
 * $mode = init | edit | fulledit | summary
 * 
 * //The following variables should ALL be printed unless NULL
 * $description = transaction description
 * $payer_uid = user selection widget (or NULL)
 * $payee_uid = user selection widget (or NULL)
 * $starter_uid = user selection widget (or NULL)
 * $completer_uid = user selection widget (or NULL)
 * $transaction_type = selection widget (or NULL)
 * $title = textfield (or NULL)
 * $quantity = textfield (or NULL)
 * $division = textfield, select widget (or NULL)
 * $state = checkbox only visible in fulledit mode
 * $next = button
 * $previous = button (only on stage 2)
 * $summary = teaser from transaction.tpl.php
 */

switch($mode) { 
  case 'fulledit':
    print '<p>'. t('Full Edit mode. Beware not to enter contradictory information') .' '.
      t('The starter and the completer must be the same two users as the payer and the payee.') .'</p>';
    print 'Payee'. $payee_uid;
    print 'Payer'. $payer_uid;
  case 'init':
  case 'edit':
    if ($starter_uid) print 'Starter:'. $starter_uid; 
    if ($completer_uid)print 'Completer:'. $completer_uid;
    if ($transaction_type) print 'Transaction type:'. $transaction_type; 
    if ($title) print 'Title: '. $title;
    if ($quantity) {
      $row = array($quantity, $division, $next);
      print 'Quantity'. theme('table', array(), array($row), array('style' => 'width:100px'));
    } 
    else {
      print $next;
    }
    break;
  case 'summary':
    print $summary;
    print $previous;
    print $next;

} 

print $state;
?>
  


<?php print $hidden_fields; ?>