<?php
// $Id$
  if (!count($exchanges)) {
    print t('No exchanges.');
    return;
  }
  
/* statement view
 * 
 * VARIABLES:
 * $exchanges array, following variables are available
 * //raw
 * $title            //the exchange title,
 * $payer_uid        //uid of payer
 * $payee_uid        //uid of payee
 * $cid              //nid of currency (0 unless currencies module installed)
 * $quantity         //quant of currency in exchange
 * $exchange_type    //readable string
 * $state            //1 = pending, 0 = completed, -1 = erased
 * $rating           //payer rating on quality of good or service exchanged
 *
 * //pre-processed
 * $title_link      //title of exchange, linked to node
 * $submitted       //formatted date
 * $amount          //formatted quantity
 * $payer           //name linked to payer profile
 * $payee           //name linked to payee profile
 * $other           //name linked to profile of other trader
 * $income OR $expenditure //formatted quantity
 * $balance         //formatted running balance
 * $classes         //array of css classes
 *
 * $ratings = TRUE if any of the listed transactions has a rating value
  [pager] => themed pager
)
 */

//need to delare the column headings
//array keys must correspond to the keys in the exchange objects
$columns = array(
  'submitted' => t('Date'),
  'title_link' => t('Item or service'),
  'other' => t('With'),
  'amount' => t('Amount'),
  'rating' => t('Rating'),
  'income' => t('Income'),
  'expenditure' => t('Expenditure'),
  'balance' => t('Running Total'),
);
if (!$ratings) unset($columns['rating']);

if (!variable_get('cc_exchange_qualities', array())) unset ($columns['quality']);
//put the given array into the columns declared to make a table
foreach($exchanges as $key => $exchange) {
  foreach ($columns as $field => $title){
    $rows[$key]['data'][$field] = $exchange[$field];
    $rows[$key]['class'] = implode(' ', $exchange['classes']);
  }
}

print theme('table', array_values($columns), $rows) .
  theme('pager', NULL, EXCHANGES_PER_PAGE, EXCHANGES_PAGER_ELEMENT);

