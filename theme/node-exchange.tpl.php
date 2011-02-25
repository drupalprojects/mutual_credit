<?php

/*
 * we'll do the preprocessing here, rather than try to interrupt the normal node preprocessing hierarchy
 * which would be inefficient
 * We have access to all the normal node fields, plus
 * $title            //the node title
 * $payer_uid        //uid of payer
 * $payee_uid        //uid of payee
 * $cid              //nid of currency (0 unless currencies module installed)
 * $quantity         //quant of currency in exchange
 * $exchange_type //either incoming_confirm, outgoing_confirm, incoming_direct or outgoing_direct, or others
 * $quality          //exchange rating - reflects on the payee
 * $state            //1 = pending, 0 = completed, -1 = erased
*/
//the preprocess function is added here because then it will only run for exchanges, not all nodes.
module_load_include('inc', 'mcapi');
extract(mc_preprocess_exchange($node));

/*
 * //makes the following available
 * $title
 * $submitted       //formatted date
 * $payer           //name linked to payer profile
 * $payee           //name linked to payee profile
 * $amount          //formatted quantity
 * $balance         //formatted running balance
 * $rating          //themed rating
 * $title_link    //title of exchange, linked to node
 * $classes         //array of css classes
 *
 */
//lumping all these together just makes the following translation strings easier

$currency = node_load($cid);
?>

<div class="exchange <?php print implode(' ', $classes); ?>">
<?php
  $page_title = t('Exchange Certificate #@nid', array('@nid' => $nid));
  if ($state == EXCHANGE_STATE_PENDING) $page_title .= '-'. strtoupper(t('pending'));
  drupal_set_title($page_title);
  ?>
  <p>On <?php print $submitted; ?></p>
   <p><?php print $payer; ?>
   <?php $state == EXCHANGE_STATE_PENDING ? print ' will pay ': print ' paid '; ?>
   <?php print $payee; ?><br /><br />
   the sum of <span style="font-size:250%"> <?php print $quantity .' '. $currency->title; ?> </span></p>
  <p>for "<strong><?php print $title; ?></strong>"
  <?php
    //links are used by the webforms module for edit/complete/delete actions
    print $links;
  ?>
</div>
