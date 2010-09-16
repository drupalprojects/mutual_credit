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
print $cid .'<br />';

print '<p>'. t(
  'I exchanged !description with !trader and now I !exchange_type !quantity',
  array(
    '!description' => '<br />'.$title ."\n<br />",
    '!trader' => '<br />'.$completer ."\n<br />",
    '!exchange_type' => '<br />'.$exchange_type ."\n<br />",
    '!quantity' => $mc_quantity ."\n<br />",
  )
) .'</p>';

if ($rating) {
  print '<p>'. t('Please rate the the goods or services exchanged:') . $rating .'</p>';
}

print $hidden;
print $buttons;
