<?php
/*
 * money.tpl.php theme an amount of money with richtext
 *
 * $sign = a minus or empty string
 * $quantity = Number, formatted according to currency type.
 * $name = the currency name (usually pluralised)
 * $currency
 * $richtext
 *
 * //all spare lines have been removed so as not to bugger up the export to csv
 */

if (!$richtext) {
  print $sign.$quantity.' '. $name;
  return;
}

$icon = '';
if (strlen($currency->icon)) {
  $img = theme(
    'image',
    $currency->icon,
    NULL,
    NULL,
    array(
      'height' => 20
    ),
    FALSE
  );
  $icon = l(
    $img,
    'node/'. $currency->nid,
    array(
      'html' => TRUE,
      'attributes' => array(
        'title' => $currency->title .' - '. $currency->body,
        'alt' => $currency->title
      )
    )
  );
}
?><span class="currency"><?php print $sign.$icon.$quantity; ?></span>