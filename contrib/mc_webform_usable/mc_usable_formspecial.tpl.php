<?php
// $Id$
/*
 * mc_webform.tpl.php
 * A usable web form transactions going in either direction in relation to the user.
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
 * $cid, a widget for selecting currency
 * $buttons
 * $rating, a dropdown, probably
 *
 */
if ($cid) {
  ?><style>.container-inline{float:inherit;}</style><?php
  $message = t('I exchanged !description with !trader and now I !exchange_type !quantity !currency');
}
else {
  $message = t('I exchanged !description with !trader and now I !exchange_type !quantity');
}

print '<p>'. t($message, array(
  '!description' => '<br />'.$title ."\n<br />",
  '!trader' => '<br />'.$completer ."\n<br />",
  '!exchange_type' => '<br />'.$exchange_type ."\n<br />",
  '!quantity' => $mc_quantity,
  '!currency' => 'in '. $cid,
  )) .'</p>
<p style="clear:left">';

if ($rating) {
  print t('Please rate the the goods or services exchanged:') . $rating .'</p>';
}

print $hidden;
print $buttons;
?>
</p>
