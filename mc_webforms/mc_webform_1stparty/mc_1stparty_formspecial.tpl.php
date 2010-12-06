<?php
// $Id$

/*
 * mc_webform.tpl.php
 * A web form for exchanges going in either direction in relation to the user.
 *
 * Variables available
 * $user = Logged in user Object
 * $form = the full form definition array
 * ... and all the others
 *
 * //The following variables should ALL be printed unless they are NULL
 * $title = transaction description
 * $completer = user selection widget (or NULL)
 * $exchange_type = selection widget (or NULL)
 * $title = textfield (or NULL)
 * $mc_quantity = textfield (or NULL)
 * $cid, a widget for selecting currency (only if currency isn't selected)
 * $buttons
 * $rating, a dropdown, probably
 */

//The exchange_type is hidden if there's only one option presented to the form.
if (!isset($exchange_type)) {
  $exchange_type = $form['exchange_type']['#options'][$form['exchange_type']['#default_value']];
}
$translations = array(
  '!description' => $title,
  '!trader' => $completer,
  '!exchange_type' => $exchange_type,
  '!quantity' => $mc_quantity,
  '!currency' => isset($cid) ? $cid : '',
  );

if (isset($cid)) {
  $message = t('I exchanged<br />!description<br />with<br />!trader<br />and now I<br />!exchange_type<br />the sum of <br />!quantity !currency', $translations);
}
else {
  $message = $message = t('I exchanged<br />!description<br />with<br />!trader<br />and now I<br />!exchange_type<br />the sum of <br />!quantity !currency', $translations);
}

print '<p>'. $message .'</p>
<p style="clear:left">';

if (isset($rating)) {
  print t('Please rate the the goods or services exchanged:') . $rating;
}

print $hidden;
print $buttons;
?></p>
<style>.container-inline{float:inherit;}</style>
