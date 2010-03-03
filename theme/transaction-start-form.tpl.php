<?php
/*
 * transaction_start_form.tpl.php 
 * An opportunity to rearrange the transaction form without using hook_form_alter.
 * Only the non-hidden fields below are avaialble as theming variables
 * If expected variables not present, check your form generation parameters and then template_preprocess_transaction_start_form()
 * 
 * Variables available
 * $user = Logged in user Object
 * $mode = init | edit | summary
 * $selector_set = both | payer_payee | starter_completer
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
 * $backdate field, if module is installed
 * $cid, a widget for selecting currency
 */
 $currency = variable_get('cc_default_currency', NULL);

if ($backdate) print t('On @date', array('@date' => $backdate)); //from an optional module
    
switch($mode) { 
  case 'init':
  case 'edit':
    if ($selector_set == 'payer_payee' || $selector_set == 'both') {
      if ($payer_uid) print t('From:'). $payer_uid;
      if ($payee_uid) print t('To:'). $payee_uid;
    }
    if ($selector_set == 'starter_completer' || $selector_set == 'both') {
      if ($starter_uid) print t('Starter:'). $starter_uid; 
      if ($completer_uid) print t('Completer:'). $completer_uid;
      if ($transaction_type) print t('Transaction type:'). $transaction_type;
    }
    if ($title) print t('Title:'). $title;
    if ($quantity) {
      $row = array($quantity, $division, $cid, $next);
      print t('Quantity'). theme('table', array(), array($row), array('style' => 'width:100px'));
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
print $hidden_fields;