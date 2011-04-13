<?php
// $Id: balance_limits.tpl.php,v 1.3.2.1 2010/09/16 12:59:27 hutch Exp $
/*
 * Balance_limits.tpl.php
 * Themed display the user's balance limits for a given currency
 * This is only called by theme_all_limits when there is only one currency to be displayed
 *
 * variables:
 *
 * $account
 * $currency entity
 * $balance array($cid => 43...);
 * $min amount (not themed)
 * $max = (not themed)
 */

$headings = array($currency->name, t('Limits'));
$data = array(
  array(t('Min'), theme('money', array('quantity' => $min, 'cid' => $currency->cid))),
  array(t('Max'), theme('money', array('quantity' => $max, 'cid' => $currency->cid))),
);
print theme('table', array('header' => $headings, 'rows' => $data, 'attributes' => array('style' => "width:100px;")));
?>