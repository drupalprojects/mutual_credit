<?php
// $Id$
/* mass payout form
one person [autocomplete] or whatever
is paying everyone except[multiselect dropdown traders only]
what for
how much
completed?

mass collect form
all traders except [ traders ]
are paying [autocomplete]
what for
how much 
completed
*/

/*
 * transaction_start_form.tpl.php 
 * An opportunity to rearrange the transaction form without using hook_form_alter.
 * Only the non-hidden fields below are avaialble as theming variables
 * If expected variables not present, check your form generation parameters and then template_preprocess_transaction_start_form()
 * 
 * Variables available
 * $user = Logged in user Object
 * $mode = init | edit | fulledit | summary
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
 */
 $currency = variable_get('cc_default_currency', NULL);
 //if ($backdate) print 'On'. $backdate; //from an optional module, not tested here
if ($non_payees) {
  print t('This account'). $payer_uid;
  print t('is paying all accounts except:'). $non_payees;
}
else {
  print t('All accounts except these'). $non_payers;
  print t('are paying'). $payee_uid;
}
print t('The transaction is for:'). $title;

$row = array($quantity, $division, $submit);
print theme('table', array(), array($row), array('style' => 'width:100px'));

print $state; 
print $hidden_fields;
