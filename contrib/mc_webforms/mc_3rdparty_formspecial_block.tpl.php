<?php
// $Id$
/*
 * mc_3rdparty_formspecial_block.tpl.php
 * A powerful default web form for entering and editing exchanges
 * N.B. This form is only called when special theming is on, see admin/mc/webforms
 *
 * Variables available
 * $user = Logged in user Object
 * $form = the full form definition array
 * ... and all the others
 *
 * //The following variables should ALL be printed unless they are NULL
 * $description = transaction description
 * $payer = user selection widget (or NULL)
 * $payee = user selection widget (or NULL)
 * $exchange_type = selection widget (or NULL)
 * $title = textfield (or NULL)
 * $mc_quantity = special field containing widgets for entering quantity
 * $state = checkbox only visible in fulledit mode
 * $summary = teaser from transaction.tpl.php
 * $cid, a widget for selecting currency
 * $buttons
 * $rating N.B. this has it's own theme callback, theme_mc_webform_ratings_field
 *
 */
?>
<style>#edit-quantity, #edit-quantity-1, #edit-division-wrapper, #edit-division-wrapper-1{float:left;} .form-item{margin:0;}</style>

<?php

print $cid .'<br />';

if (isset($payer))
  print t('Payer') .':'. $payer;
if (isset($payee))
  print t('Payee') .':'. $payee;
if (isset($exchange_type))
  print t('Exchange type') .':'. $exchange_type;
if (isset($title))
  print t('Description') .':'. $title;
if (isset($mc_quantity))
  print t('Quantity') .':'. $mc_quantity;

print $state;

if (isset($rating)) print '<p>'. t('Payer rating:') . $rating .'</p>';
print $hidden;
print $buttons;
