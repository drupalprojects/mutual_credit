<?php
/*
 * money.tpl.php theme an amount of money with richtext
 *
 * $sign = a minus or empty string
 * $icon = an <img> tag, if applicable
 * $quantity = Number, formatted according to currency type.
 */
?>
<span class="currency"><?php print $sign.$icon.$quantity ?></span>

